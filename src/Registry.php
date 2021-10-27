<?php

namespace Skynet;

use BN\BN;
use Exception;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use Requests_Exception_HTTP;
use Skynet\Options\CustomClientOptions;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Traits\BaseMethods;
use Skynet\Options\CustomSetEntryOptions;
use Skynet\Types\RegistryEntry;
use Skynet\Types\SignedRegistryEntry;
use function Skynet\functions\crypto\hashDataKey;
use function Skynet\functions\crypto\hashRegistryEntry;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\options\makeOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\registry\getEntryUrlForPortal;
use function Skynet\functions\registry\signEntry;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\validation\assertUint64;
use function Skynet\functions\validation\validateHexString;
use function Skynet\functions\validation\validateRegistryEntry;
use function Skynet\functions\validation\validateString;
use function Skynet\functions\validation\validateUint8ArrayLen;
use function Sodium\crypto_sign_publickey_from_secretkey;
use function Sodium\crypto_sign_verify_detached;

/**
 *
 */
class Registry {
	use BaseMethods;

	/**
	 *
	 */
	const DEFAULT_GET_ENTRY_OPTIONS = [
		'apiKey'           => '',
		'customUserAgent'  => '',
		'customCookie'     => '',
		'endpointGetEntry' => '/skynet/registry',
		'hashedDataKeyHex' => false,
	];
	/**
	 *
	 */
	const DEFAULT_SET_ENTRY_OPTIONS = [
		'apiKey'           => '',
		'customUserAgent'  => '',
		'customCookie'     => '',
		'endpointSetEntry' => '/skynet/registry',
		'hashedDataKeyHex' => false,
	];
	/**
	 *
	 */
	const REGISTRY_TYPE_WITHOUT_PUBKEY = 1;
	/**
	 *
	 */
	const MAX_ENTRY_LENGTH = 70;
	/**
	 * @var \BN\BN|null
	 */
	private static ?BN $MAX_REVISION = null;
	/**
	 * @var \Skynet\Skynet|null
	 */
	private Skynet $skynet;

	/**
	 * @param \Skynet\Skynet|null                      $skynet
	 * @param \Skynet\Options\CustomClientOptions|null $options
	 */
	public function __construct( Skynet $skynet = null, ?CustomClientOptions $options = null ) {
		if ( null === $skynet ) {
			$skynet = new Skynet();
		}

		self::getMaxRevision();

		if ( null !== $options ) {
			$options = makeOptions( CustomClientOptions::class, mergeOptions( Skynet::DEFAULT_BASE_OPTIONS, $options ) );
		} else {
			$options = new CustomClientOptions( Skynet::DEFAULT_BASE_OPTIONS );
		}

		$this->skynet  = $skynet;
		$this->options = $options;
	}

	/**
	 * @return \BN\BN
	 */
	public static function getMaxRevision(): BN {
		if ( null === self::$MAX_REVISION ) {
			self::$MAX_REVISION = new BN( "18446744073709551615" );
		}

		return self::$MAX_REVISION;
	}

	/**
	 * @return \Skynet\Skynet|null
	 */
	public function getSkynet(): ?Skynet {
		return $this->skynet;
	}

	/**
	 * @param string                                     $publicKey
	 * @param string                                     $dataKey
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 *
	 * @return \Skynet\Types\SignedRegistryEntry
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getEntry( string $publicKey, string $dataKey, ?CustomGetEntryOptions $options = null ): SignedRegistryEntry {
		$options = $this->buildGetEntryOptions( $options );

		$url = $this->getEntryUrl( $publicKey, $dataKey, $options );

		/** @var \GuzzleHttp\Psr7\Response $response */
		$response = null;

		try {
			$response = $this->executeRequest( $this->buildRequestOptions( mergeOptions(
				$options,
				[
					'endpointPath' => $options->getEndpointGetEntry(),
					'url'          => $url,
					'method'       => 'GET',

				],
			) ) );
			/** @noinspection NotOptimalIfConditionsInspection */
			if ( ! ( $response->getStatusCode() >= 200 && $response->getStatusCode() < 300 ) ) {
				throw new BadResponseException( '', new Request( 'GET', $url ), $response );
			}

		} catch ( BadResponseException $e ) {
			if ( 404 === $e->getResponse()->getStatusCode() ) {
				return new SignedRegistryEntry(  );
			}
			throw new Exception( sprintf( 'Request failed with status code %d', $e->getResponse()->getStatusCode() ) );
		}

		$body = $response->getBody()->getContents();

		try {
			$body = preg_replace( REGEX_REVISION_NO_QUOTES, '"revision":"$1"', $body );
			$body = json_decode( $body );

			validateString( "response->body->data", $body->data ?? null, "entry response field" );
			validateString( "response->body->revision", $body->revision ?? null, "entry response field" );
			validateString( "response->body->signature", $body->signature ?? null, "entry response field" );
		} catch ( Exception $e ) {
			throw new Exception( sprintf( 'Did not get a complete entry response despite a successful request. Please try again and report this issue to the devs if it persists. Error: %s', $e->getMessage() ) );
		}

		$revision  = new BN( $body->revision );
		$signature = hexToUint8Array( $body->signature );

		$data = new Uint8Array();
		if ( $body->data ) {
			$data = hexToUint8Array( $body->data );
		}

		$signedEntry = new SignedRegistryEntry(
			[
				'entry'     => new RegistryEntry( $dataKey, $data, $revision ),
				'signature' => $signature,
			] );

		$signatureBytes = $signedEntry->getSignature();
		$publicKeyBytes = hexToUint8Array( $publicKey );

		validateUint8ArrayLen( "signatureArray", $signatureBytes, "response value", SIGNATURE_LENGTH );
		validateUint8ArrayLen( "publicKeyArray", $publicKeyBytes, "response value", PUBLIC_KEY_LENGTH / 2 );

		/** @noinspection NullPointerExceptionInspection */
		if ( crypto_sign_verify_detached( $signatureBytes->toString(), hashRegistryEntry( $signedEntry->getEntry(), $options->isHashedDataKeyHex() )->toString(), $publicKeyBytes->toString() ) ) {
			return $signedEntry;
		}

		throw new Exception( 'Could not verify signature from retrieved, signed registry entry -- possible corrupted entry' );
	}

	/**
	 * @param string                                     $publicKey
	 * @param string                                     $dataKey
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 *
	 * @return string
	 */
	public function getEntryUrl( string $publicKey, string $dataKey, ?CustomGetEntryOptions $options = null ) {
		$options = $this->buildGetEntryOptions( $options );

		$portalUrl = $this->skynet->getPortalUrl();

		return getEntryUrlForPortal( $portalUrl, $publicKey, $dataKey, $options );
	}

	/**
	 * @param string                                     $privateKey
	 * @param \Skynet\Types\RegistryEntry                $entry
	 * @param \Skynet\Options\CustomSetEntryOptions|null $options
	 *
	 * @return void
	 * @throws \SodiumException
	 */
	public function setEntry( string $privateKey, RegistryEntry $entry, ?CustomSetEntryOptions $options = null ): void {
		validateHexString( "privateKey", $privateKey, "parameter" );
		validateRegistryEntry( "entry", $entry, "parameter" );

		assertUint64( $entry->getRevision() );

		$options = $this->buildSetEntryOptions( $options );

		$privateKeyArray = hexToUint8Array( $privateKey );
		$signature       = signEntry( $privateKey, $entry, $options->getHashedDataKeyHex() );
		$publicKey = crypto_sign_publickey_from_secretkey( $privateKeyArray->toString() );

		$this->postSignedEntry( toHexString( $publicKey ), $entry, $signature, $options );

	}

	/**
	 * @param \Skynet\Options\CustomSetEntryOptions|null $options
	 * @param array                                      $funcOptions
	 *
	 * @return \Skynet\Options\CustomSetEntryOptions
	 */
	private function buildSetEntryOptions( CustomSetEntryOptions $options = null, array $funcOptions = [] ): CustomSetEntryOptions {
		return $this->buildOptions( self::DEFAULT_SET_ENTRY_OPTIONS, CustomSetEntryOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                     $publicKey
	 * @param \Skynet\Types\RegistryEntry                $entry
	 * @param \Skynet\Uint8Array                         $signature
	 * @param \Skynet\Options\CustomSetEntryOptions|null $options
	 *
	 * @return void
	 * @throws \Requests_Exception
	 */
	public function postSignedEntry( string $publicKey, RegistryEntry $entry, Uint8Array $signature, ?CustomSetEntryOptions $options = null ): void {
		validateHexString( "publicKey", $publicKey, "parameter" );
		validateRegistryEntry( "entry", $entry, "parameter" );

		$options = $this->buildSetEntryOptions( $options );

		$datakey = $entry->getDataKey();
		if ( ! $options->getHashedDataKeyHex() ) {
			$datakey = toHexString( hashDataKey( $datakey ) );
		}

		$entryData = Uint8Array::from ( $entry->getData() );

		$data = [
			'publickey' => [
				'algorithm' => 'ed25519',
				'key'       => hexToUint8Array( $publicKey )->getData(),
			],
			'datakey'   => $datakey,
			'data'      => $entryData->getData(),
			'revision'  => $entry->getRevision()->toString(),
			'signature' => $signature->getData(),
		];

		$data = json_encode( $data );
		$data = preg_replace( REGEX_REVISION_WITH_QUOTES, '"revision":$1', $data );

		/** @var CustomSetEntryOptions $options */
		$this->executeRequest( $this->buildRequestOptions(
			$options->toArray(),
			[
				'endpointPath' => $options->getEndpointSetEntry(),
				'method'       => 'POST',
				'data'         => $data,
			],
		 ) );
	}
}
