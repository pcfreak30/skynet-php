<?php

namespace Skynet\functions\registry;


use BN\BN;
use Exception;
use JsonException;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Options\CustomValidateRegistryProofOptions;
use Skynet\Registry;
use Skynet\Types\RegistryEntry;
use Skynet\Types\RegistryProof;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\hashDataKey;
use function Skynet\functions\crypto\hashRegistryEntry;
use function Skynet\functions\encoding\encodeSkylinkBase64;
use function Skynet\functions\formatting\formatSkylink;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\options\makeGetEntryOptions;
use function Skynet\functions\options\makeValidateRegistryProofOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\sia\isSkylinkV1;
use function Skynet\functions\sia\newEd25519PublicKey;
use function Skynet\functions\sia\newSkylinkV2;
use function Skynet\functions\strings\ensurePrefix;
use function Skynet\functions\strings\hexToString;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\strings\trimPrefix;
use function Skynet\functions\url\add_query_arg;
use function Skynet\functions\url\makeUrl;
use function Skynet\functions\url\trimUriPrefix;
use function Skynet\functions\validation\validateHexString;
use function Skynet\functions\validation\validatePublicKey;
use function Skynet\functions\validation\validateRegistryEntry;
use function Sodium\crypto_sign;
use function Sodium\crypto_sign_verify_detached;
use const Skynet\DEFAULT_GET_ENTRY_TIMEOUT;
use const Skynet\ED25519_PREFIX;
use const Skynet\REGISTRY_TYPE_WITHOUT_PUBKEY;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @param string      $inputSkylink
 * @param string      $dataLink
 * @param string|null $proof
 *
 * @return void
 * @throws \JsonException
 */
function validateRegistryProofResponse( string $inputSkylink, string $dataLink, string $proof = null ): void {
	$proofArray = [];

	try {
		if ( $proof ) {
			try {
				if ( 'null' === $proof ) {
					$proof = null;
				}
				$proofArray = json_decode( $proof, false, 512, JSON_THROW_ON_ERROR );
			} catch ( JsonException $e ) {
				throw new Exception( "Could not parse 'skynet-proof' header as JSON" );
			}
		}
	} catch ( Exception $e ) {
		throw $e;
	}

	if ( isSkylinkV1( $inputSkylink ) ) {
		if ( $inputSkylink !== $dataLink ) {
			throw new Exception( 'Expected returned skylink to be the same as input data link' );
		}
		if ( $proof ) {
			throw new Exception( "Expected 'skynet-proof' header to be empty for data link" );
		}

		return;
	}

	if ( $inputSkylink === $dataLink ) {
		throw new Exception( 'Expected returned skylink to be different from input entry link' );
	}

	$proofOptions = makeValidateRegistryProofOptions( [
		'resolverSkylink' => $inputSkylink,
		'skylink'         => $dataLink,
	] );

	foreach ( $proofArray as $index => $proofItem ) {
		$proofArray[ $index ] = new RegistryProof( (array) $proofItem );
	}

	validateRegistryProof( $proofArray, $proofOptions );
}

/**
 * @param array                                                   $proof
 * @param \Skynet\Options\CustomValidateRegistryProofOptions|null $options
 *
 * @return array
 * @throws \SodiumException
 */
function validateRegistryProof( array $proof, CustomValidateRegistryProofOptions $options = null ) {
	$resolverSkylink = null;
	$lastSkylink     = null;
	$dataLink        = null;

	if ( $options ) {
		$lastSkylink = $options->getResolverSkylink();
		$dataLink    = $options->getSkylink();
	}

	if ( 0 === count( $proof ) ) {
		throw new Exception( 'Expected registry proof not to be empty' );
	}

	/** @var RegistryProof $entry */
	foreach ( $proof as $entry ) {
		if ( $entry->getType() !== REGISTRY_TYPE_WITHOUT_PUBKEY ) {
			throw new Exception( sprintf( "Unsupported registry type in proof: '%s'", $entry->getType() ) );
		}

		$publicKey      = $entry->getPublickey()->key;
		$publicKeyBytes = Uint8Array::from( $publicKey );
		$publicKeyHex   = toHexString( $publicKeyBytes );
		$dataKey        = $entry->getDatakey();
		$data           = $entry->getData();
		$signatureBytes = hexToUint8Array( $entry->getSignature() );

		$entryLink = getEntryLink( $publicKeyHex, $dataKey, makeGetEntryOptions( [ 'hashedDataKeyHex' => true ] ) );
		$entryLink = trimUriPrefix( $entryLink, URI_SKYNET_PREFIX );

		if ( $lastSkylink && $entryLink !== $lastSkylink ) {
			throw new Exception( 'Could not verify registry proof chain' );
		}

		if ( ! $resolverSkylink ) {
			$resolverSkylink = $entryLink;
		}

		$rawData = hexToUint8Array( $data );
		$skylink = encodeSkylinkBase64( $rawData );

		$entryToVerify = new RegistryEntry( $dataKey, $rawData, new BN( $entry->getRevision() ) );
		if ( ! crypto_sign_verify_detached( $signatureBytes->toString(), hashRegistryEntry( $entryToVerify, true )->toString(), $publicKeyBytes->toString() ) ) {
			throw  new Exception( 'Could not verify signature from retrieved, signed registry entry in registry proof' );
		}

		$lastSkylink = $skylink;
	}

	if ( $dataLink && $lastSkylink !== $dataLink ) {
		throw new Exception( 'Could not verify registry proof chain' );
	}

	return [ 'skylink' => $lastSkylink, 'resolverSkylink' => $resolverSkylink ];
}

/**
 * @param string                                     $publicKey
 * @param string                                     $dataKey
 * @param \Skynet\Options\CustomGetEntryOptions|null $options
 *
 * @return string
 * @throws \SodiumException
 */
function getEntryLink( string $publicKey, string $dataKey, ?CustomGetEntryOptions $options = null ) {

	/** @noinspection CallableParameterUseCaseInTypeContextInspection */
	$options = mergeOptions( Registry::DEFAULT_GET_ENTRY_OPTIONS, $options );
	/** @noinspection CallableParameterUseCaseInTypeContextInspection */
	$options = makeGetEntryOptions( $options );

	$siaPublicKey = newEd25519PublicKey( trimPrefix( $publicKey, ED25519_PREFIX ) );
	if ( $options->isHashedDataKeyHex() ) {
		$tweak = hexToUint8Array( $dataKey );
	} else {
		$tweak = hashDataKey( $dataKey );
	}

	$skylink = newSkylinkV2( $siaPublicKey, $tweak )->toString();

	return formatSkylink( $skylink );
}


/**
 * @param string                                     $portalUrl
 * @param string                                     $publicKey
 * @param string                                     $dataKey
 * @param \Skynet\Options\CustomGetEntryOptions|null $options
 *
 * @return string
 * @throws \SodiumException
 */
function getEntryUrlForPortal( string $portalUrl, string $publicKey, string $dataKey, ?CustomGetEntryOptions $options = null ) {
	validatePublicKey( 'publicKey', $publicKey, 'parameter' );

	/** @var CustomGetEntryOptions $options */
	$options = makeGetEntryOptions( mergeOptions( Registry::DEFAULT_GET_ENTRY_OPTIONS, $options ) );

	$dataKeyHashHex = $dataKey;
	if ( ! $options->isHashedDataKeyHex() ) {
		$dataKeyHashHex = toHexString( hashDataKey( $dataKey ) );
	}

	$query = [
		'publickey' => urlencode( ensurePrefix( $publicKey, ED25519_PREFIX ) ),
		'datakey'   => $dataKeyHashHex,
		'timeout'   => DEFAULT_GET_ENTRY_TIMEOUT,
	];

	$url = makeUrl( $portalUrl, $options->getEndpointGetEntry() );
	$url = add_query_arg( $query, $url );

	return $url;
}

/**
 * @param string                      $privateKey
 * @param \Skynet\Types\RegistryEntry $entry
 * @param bool                        $hashedDataKeyHex
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function signEntry( string $privateKey, RegistryEntry $entry, bool $hashedDataKeyHex ): Uint8Array {
	validateHexString( "privateKey", $privateKey, "parameter" );
	validateRegistryEntry( "entry", $entry, "parameter" );

	$privateKeyArray = hexToString( $privateKey );

	return Uint8Array::from( crypto_sign( hashRegistryEntry( $entry, $hashedDataKeyHex )->toString(), $privateKeyArray ) );
}
