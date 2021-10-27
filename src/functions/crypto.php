<?php

namespace Skynet\functions\crypto;

use Skynet\Types\KeyPair;
use Skynet\Types\KeyPairAndSeed;
use Skynet\Types\RegistryEntry;
use Skynet\Uint8Array;
use function Skynet\functions\encoding\encodeBigintAsUint64;
use function Skynet\functions\encoding\encodePrefixedBytes;
use function Skynet\functions\encoding\encodeUtf8String;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\strings\hexToUint8Array;
use function Sodium\crypto_generichash_final;
use function Sodium\crypto_generichash_init;
use function Sodium\crypto_generichash_update;
use function Sodium\crypto_sign_publickey;
use function Sodium\crypto_sign_secretkey;
use function Sodium\crypto_sign_seed_keypair;
use function Sodium\randombytes_buf;

/**
 * @param \Skynet\Uint8Array ...$args
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function hashAll( Uint8Array ...$args ): Uint8Array {
	$hasher = crypto_generichash_init();

	foreach ( $args as $item ) {
		crypto_generichash_update( $hasher, (string) $item );
	}
	$result = crypto_generichash_final( $hasher );

	return Uint8Array::from( $result );
}

/**
 * @param string $dataKey
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function hashDataKey( string $dataKey ): Uint8Array {
	return hashAll( encodeUtf8String( $dataKey ) );
}

/**
 * @param \Skynet\Types\RegistryEntry $registryEntry
 * @param bool                        $hashedDataKeyHex
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function hashRegistryEntry( RegistryEntry $registryEntry, bool $hashedDataKeyHex ): Uint8Array {
	if ( $hashedDataKeyHex ) {
		$dataKeyBytes = hexToUint8Array( $registryEntry->getDataKey() );
	} else {
		$dataKeyBytes = hashDataKey( $registryEntry->getDataKey() );
	}

	$dataBytes = encodePrefixedBytes( $registryEntry->getData() );

	return hashAll( $dataKeyBytes, $dataBytes, encodeBigintAsUint64( $registryEntry->getRevision() ) );
}

/**
 * @param string $masterSeed
 * @param string $seed
 *
 * @return string
 * @throws \SodiumException
 */
function deriveChildSeed( string $masterSeed, string $seed ) {
	return ( hashAll( encodeUtf8String( $masterSeed ), encodeUtf8String( $seed ) ) )->toHex();
}

/**
 * @param int $length
 *
 * @return \Skynet\Types\KeyPairAndSeed
 */
function genKeyPairAndSeed( int $length = 64 ): KeyPairAndSeed {
	$seed = makeSeed( $length );

	return KeyPairAndSeed::fromSeed( $seed );
}

/**
 * @param int $length
 *
 * @return string
 */
function makeSeed( int $length ): string {
	$bytes = randombytes_buf( $length );

	return bin2hex( $bytes );
}

/**
 * @param string $seed
 *
 * @return \Skynet\Types\KeyPair
 * @throws \SodiumException
 */
function genKeyPairFromSeed( string $seed ): KeyPair {

	$derivedKey = hash_pbkdf2( 'sha256', $seed, '', 1000, 32, true );
	$keyPair    = crypto_sign_seed_keypair( $derivedKey );

	return new KeyPair(
		[
			'publicKey'  => toHexString( crypto_sign_publickey( $keyPair ) ),
			'privateKey' => toHexString( crypto_sign_secretkey( $keyPair ) ),
		] );
}
