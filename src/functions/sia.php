<?php

namespace Skynet\functions\sia;

use Exception;
use Skynet\SiaPublicKey;
use Skynet\SiaSkylink;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\hashAll;
use function Skynet\functions\formatting\decodeSkylinkBase32;
use function Skynet\functions\formatting\decodeSkylinkBase64;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\strings\trimPrefix;
use const Skynet\BASE32_ENCODED_SKYLINK_SIZE;
use const Skynet\BASE64_ENCODED_SKYLINK_SIZE;
use const Skynet\ERR_SKYLINK_INCORRECT_SIZE;
use const Skynet\RAW_SKYLINK_SIZE;
use const Skynet\SPECIFIER_LEN;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @param string $name
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function newSpecifier( string $name ): Uint8Array {
	$specifier = new Uint8Array( SPECIFIER_LEN );
	$specifier->set( $name );

	return $specifier;
}

/**
 * @param string $publicKey
 *
 * @return \Skynet\SiaPublicKey
 * @throws \Exception
 */
function newEd25519PublicKey( string $publicKey ): SiaPublicKey {
	$algo           = newSpecifier( 'ed25519' );
	$publicKeyBytes = hexToUint8Array( $publicKey );

	return new SiaPublicKey( $algo, $publicKeyBytes );
}

/**
 * @param \Skynet\SiaPublicKey $siaPublicKey
 * @param \Skynet\Uint8Array   $tweak
 *
 * @return \Skynet\SiaSkylink
 */
function newSkylinkV2( SiaPublicKey $siaPublicKey, Uint8Array $tweak ): SiaSkylink {
	$version    = 2;
	$bitfield   = $version - 1;
	$merkleRoot = deriveRegistryEntryID( $siaPublicKey, $tweak );

	return new SiaSkylink( $bitfield, $merkleRoot );
}

/**
 * @param \Skynet\SiaPublicKey $pubKey
 * @param \Skynet\Uint8Array   $tweak
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function deriveRegistryEntryID( SiaPublicKey $pubKey, Uint8Array $tweak ): Uint8Array {
	return hashAll( $pubKey->marshalSia(), $tweak );
}

/**
 * @param string $skylink
 *
 * @return bool
 * @throws \Exception
 */
function isSkylinkV1( string $skylink ): bool {
	$raw = decodeSkylink( $skylink );

	return isBitfieldSkylinkV1( $raw[0] );
}

/**
 * @param string $skylink
 *
 * @return bool
 * @throws \Exception
 */
function isSkylinkV2( string $skylink ): bool {
	$raw = decodeSkylink( $skylink );

	return isBitfieldSkylinkV2( $raw[0] );
}

/**
 * @param int $bitfield
 *
 * @return bool
 */
function isBitfieldSkylinkV1( int $bitfield ) {
	return ( $bitfield & 3 ) === 0;
}

/**
 * @param int $bitfield
 *
 * @return bool
 */
function isBitfieldSkylinkV2( int $bitfield ) {
	return $bitfield === 1;
}


/**
 * @param string $encoded
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function decodeSkylink( string $encoded ): Uint8Array {
	$encoded = trimPrefix( $encoded, URI_SKYNET_PREFIX );
	$length  = strlen( $encoded );
	if ( $length === BASE32_ENCODED_SKYLINK_SIZE ) {
		$bytes = decodeSkylinkBase32( $encoded );
	} else if ( $length === BASE64_ENCODED_SKYLINK_SIZE ) {
		$bytes = decodeSkylinkBase64( $encoded );
	} else {
		throw  new Exception( ERR_SKYLINK_INCORRECT_SIZE );
	}

	if ( strlen( $bytes ) !== RAW_SKYLINK_SIZE ) {
		throw new Exception( "failed to load skylink data" );
	}

	return $bytes;
}
