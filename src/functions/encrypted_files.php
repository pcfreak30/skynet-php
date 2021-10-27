<?php

namespace Skynet\functions\encrypted_files;

use Skynet\MySky;
use Skynet\Types\EncryptedFileMetadata;
use Skynet\Uint8Array;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\validation\validateUint8ArrayLen;
use function Sodium\crypto_secretbox_open;

/**
 * @param \Skynet\Uint8Array $data
 * @param \Skynet\Uint8Array $key
 *
 * @return \stdClass
 * @throws \Exception
 */
function decryptJSONFile( Uint8Array $data, Uint8Array $key ): \stdClass {
	validateUint8ArrayLen( "key", $key, "parameter", MySky::ENCRYPTION_KEY_LENGTH );

	if ( ! checkPaddedBlock( $data->getMaxLength() ) ) {
		$paddedSize = padFileSize( $data->getMaxLength() );
		throw new \Exception(
			sprintf( "Expected parameter 'data' to be padded encrypted data, length was '%d', nearest padded block is '%d'", $data->getMaxLength(), $paddedSize )
		);
	}

	$nonce = $data->slice( 0, MySky::ENCRYPTION_NONCE_LENGTH );
	$data  = $data->slice( MySky::ENCRYPTION_NONCE_LENGTH );

	$metadataBytes = $data->slice( 0, MySky::ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH );
	$data          = $data->slice( MySky::ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH );
	$metadata      = decodeEncryptedFileMetadata( $metadataBytes );

	if ( $metadata->getVersion() !== MySky::ENCRYPTED_JSON_RESPONSE_VERSION ) {
		throw new \Exception( sprintf(
			"Received unrecognized JSON response version '%s' in metadata, expected '%s'", $metadata->getVersion(), MySky::ENCRYPTED_JSON_RESPONSE_VERSION ) );
	}

	$decryptedBytes = crypto_secretbox_open( $data->toString(), $nonce->toString(), $key->toString() );
	if ( ! $decryptedBytes ) {
		throw new \Exception( 'Could not decrypt given encrypted JSON file' );
	}

	$paddingIndex = strlen( $decryptedBytes );

	while ( $paddingIndex > 0 && 0 === ord( $decryptedBytes[ $paddingIndex - 1 ] ) ) {
		$paddingIndex --;
	}

	$decryptedBytes = substr( $decryptedBytes, 0, $paddingIndex );

	return json_decode( $decryptedBytes );

}

/**
 * @param string $pathSeed
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function deriveEncryptedFileKeyEntropy( string $pathSeed ): Uint8Array {
	$bytes     = Uint8Array::from( sha512( MySky::SALT_ENCRYPTION ) . sha512( $pathSeed ) );
	$hashBytes = sha512( $bytes->toString() );

	return Uint8Array::from( substr( $hashBytes, 0, MySky::ENCRYPTION_KEY_LENGTH ) );
}

/**
 * @param \stdClass                           $json
 * @param \Skynet\Types\EncryptedFileMetadata $metadata
 * @param \Skynet\Uint8Array                  $key
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function encryptJSONFile( \stdClass $json, EncryptedFileMetadata $metadata, Uint8Array $key ): Uint8Array {
	validateUint8ArrayLen( 'key', $key, 'parameter', MySky::ENCRYPTION_KEY_LENGTH );

	$data = json_encode( $json );

	$totalOverhead = MySky::ENCRYPTION_OVERHEAD_LENGTH + MySky::ENCRYPTION_NONCE_LENGTH + MySky::ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH;
	$finalSize     = padFileSize( strlen( $data ) + $totalOverhead ) - $totalOverhead;

	$data = Uint8Array::from( $data, $finalSize );

	$nonce          = random_bytes( MySky::ENCRYPTION_NONCE_LENGTH );
	$encryptedBytes = sodium_crypto_secretbox( $data->toString(), $nonce, $key->toString() );

	$metadataBytes = encodeEncryptedFileMetadata( $metadata );

	return Uint8Array::from( $nonce . $metadataBytes->toString() . $encryptedBytes );
}

/**
 * @param \Skynet\Types\EncryptedFileMetadata $metadata
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function encodeEncryptedFileMetadata( EncryptedFileMetadata $metadata ) {
	$bytes = new Uint8Array( MySky::ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH );

	if ( $metadata->getVersion() >= 1 << 8 || $metadata->getVersion() < 0 ) {
		throw new \Exception( sprintf( "Metadata version '%d' could not be stored in a uint8", $metadata->getVersion() ) );
	}

	$bytes[0] = $metadata->getVersion();

	return $bytes;
}

/**
 * @param int $initialSize
 *
 * @return int
 * @throws \Exception
 */
function padFileSize( int $initialSize ): int {
	$kib = 1 << 10;

	for ( $n = 0; $n < 53; $n ++ ) {
		if ( $initialSize <= ( 1 << $n ) * 80 * $kib ) {
			$paddingBlock = ( 1 << $n ) * 4 * $kib;
			$finalSize    = $initialSize;
			if ( $finalSize % $paddingBlock !== 0 ) {
				$finalSize = $initialSize - ( $initialSize % $paddingBlock ) + $paddingBlock;
			}

			return (int) $finalSize;
		}
	}
	throw new \Exception( 'Could not pad file size, overflow detected.' );
}

/**
 * @param string $pathSeed
 *
 * @return string
 * @throws \Exception
 */
function deriveEncryptedFileTweak( string $pathSeed ): string {
	$hashBytes = sha512( sha512( MySky::SALT_ENCRYPTED_TWEAK ) . sha512( $pathSeed ) );
	$hashBytes = substr( $hashBytes, 0, MySky::HASH_LENGTH );

	return toHexString( $hashBytes );
}

/**
 * @param string $string
 *
 * @return string
 */
function sha512( string $string ): string {
	return hash( 'sha512', $string, true );
}

/**
 * @param int $size
 *
 * @return bool
 * @throws \Exception
 */
function checkPaddedBlock( int $size ): bool {
	$kib = 1 << 10;

	for ( $n = 0; $n < 53; $n ++ ) {
		if ( $size <= ( 1 << $n ) * 80 * $kib ) {
			$paddingBlock = ( 1 << $n ) * 4 * $kib;

			return 0 === $size % $paddingBlock;
		}
	}
	throw new \Exception( 'Could not check padded file size, overflow detected.' );
}

/**
 * @param \Skynet\Uint8Array $bytes
 *
 * @return \Skynet\Types\EncryptedFileMetadata
 */
function decodeEncryptedFileMetadata( Uint8Array $bytes ): EncryptedFileMetadata {
	validateUint8ArrayLen( 'bytes', $bytes, 'encrypted file metadata bytes', MySky::ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH );

	$version = $bytes[0];

	return new EncryptedFileMetadata( [ 'version' => $version ] );
}
