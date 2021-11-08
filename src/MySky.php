<?php

namespace Skynet;

use Skynet\Options\CustomConnectorOptions;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Options\CustomGetJSONOptions;
use Skynet\Options\CustomSetEntryOptions;
use Skynet\Options\CustomSetJSONOptions;
use Skynet\Traits\BaseMethods;
use Skynet\Types\EncryptedJSONResponse;
use Skynet\Types\EntryData;
use Skynet\Types\JSONResponse;
use Skynet\Types\KeyPairAndSeed;
use Skynet\Types\RegistryEntry;
use function Skynet\functions\encoding\encodeSkylinkBase64;
use function Skynet\functions\formatting\formatSkylink;
use function Skynet\functions\misc\arrayToObject;
use function Skynet\functions\mysky\generateSeedPhrase;
use function Skynet\functions\mysky\genKeyPairFromSeed;
use function Skynet\functions\encrypted_files\decryptJSONFile;
use function Skynet\functions\encrypted_files\deriveEncryptedFileKeyEntropy;
use function Skynet\functions\encrypted_files\deriveEncryptedFileTweak;
use function Skynet\functions\encrypted_files\encryptJSONFile;
use function Skynet\functions\encrypted_files\sha512;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\mysky\deriveEncryptedFileSeed;
use function Skynet\functions\mysky\validatePhrase;
use function Skynet\functions\options\extractOptions;
use function Skynet\functions\options\makeClientOptions;
use function Skynet\functions\options\makeEncryptedFileMetadata;
use function Skynet\functions\options\makeGetEntryOptions;
use function Skynet\functions\options\makeOptions;
use function Skynet\functions\options\makeSetEntryOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\registry\getEntryLink;
use function Skynet\functions\registry\signEntry;
use function Skynet\functions\sia\decodeSkylink;
use function Skynet\functions\tweak\deriveDiscoverableFileTweak;
use function Skynet\functions\validation\throwValidationError;

/**
 *
 */
class MySky {
	use BaseMethods;

	/**
	 *
	 */
	const DEFAULT_CONNECTOR_OPTIONS = [
		'dev'                       => false,
		'debug'                     => false,
		'alpha'                     => false,
		'handshakeMaxAttempts'      => DEFAULT_HANDSHAKE_MAX_ATTEMPTS,
		'handshakeAttemptsInterval' => DEFAULT_HANDSHAKE_ATTEMPTS_INTERVAL,
	];
	/**
	 *
	 */
	const SALT_ENCRYPTED_PATH_SEED = 'encrypted filesystem path seed';
	/**
	 *
	 */
	const SALT_ENCRYPTED_CHILD = 'encrypted filesystem child';
	/**
	 *
	 */
	const SALT_ENCRYPTED_TWEAK = 'encrypted filesystem tweak';
	/**
	 *
	 */
	const SALT_ENCRYPTION = 'encryption';
	/**
	 *
	 */
	const ENCRYPTION_PATH_SEED_LENGTH = 32;
	/**
	 *
	 */
	const ENCRYPTION_KEY_LENGTH = 32;
	/**
	 *
	 */
	const ENCRYPTION_NONCE_LENGTH = 24;
	/**
	 *
	 */
	const ENCRYPTION_HIDDEN_FIELD_METADATA_LENGTH = 16;
	/**
	 *
	 */
	const ENCRYPTED_JSON_RESPONSE_VERSION = 1;
	/**
	 *
	 */
	const ENCRYPTION_OVERHEAD_LENGTH = 16;
	/**
	 *
	 */
	const HASH_LENGTH = 32;
	/**
	 * @var \Skynet\Options\CustomConnectorOptions|mixed|\Skynet\Entity
	 */
	private CustomConnectorOptions $connectionOptions;
	/**
	 * @var \Skynet\Types\KeyPair
	 */
	private KeyPairAndSeed $key;
	/**
	 * @var \Skynet\Db|null
	 */
	private Db $db;

	/**
	 * @param string|null                                 $seed
	 * @param \Skynet\Db|null                             $db
	 * @param \Skynet\Options\CustomConnectorOptions|null $options
	 *
	 * @throws \Exception
	 */
	public function __construct( string $seed = null, ?Db $db = null, ?CustomConnectorOptions $options = null ) {
		if ( null === $db ) {
			$db = new Db();
		}

		if ( null !== $options ) {
			$options = makeOptions( CustomConnectorOptions::class, mergeOptions( self::DEFAULT_CONNECTOR_OPTIONS, $options ) );
		} else {
			$options = new CustomConnectorOptions( self::DEFAULT_CONNECTOR_OPTIONS );
		}

		if ( null === $seed ) {
			$seed = generateSeedPhrase();
		}

		[ $valid, $error ] = validatePhrase( $seed );
		if ( ! $valid || ! $seed ) {
			throw new \Exception( $error );
		}

		$key = KeyPairAndSeed::fromSeed( $seed );

		$this->db                = $db;
		$this->connectionOptions = $options;
		$this->key               = $key;
		$this->options           = makeClientOptions( [] );
	}

	/**
	 * @param \Skynet\Registry $registry
	 */
	public function setRegistry( Registry $registry ): void {
		$this->registry = $registry;
	}

	/**
	 * @return mixed|\Skynet\Entity|\Skynet\Options\CustomConnectorOptions
	 */
	public function getConnectionOptions() {
		return $this->connectionOptions;
	}

	/**
	 * @param mixed|\Skynet\Entity|\Skynet\Options\CustomConnectorOptions $connectionOptions
	 */
	public function setConnectionOptions( $connectionOptions ): void {
		$this->connectionOptions = $connectionOptions;
	}

	/**
	 * @return \Skynet\Types\KeyPairAndSeed
	 */
	public function getKey(): KeyPairAndSeed {
		return $this->key;
	}

	/**
	 * @param \Skynet\Types\KeyPairAndSeed $key
	 */
	public function setKey( KeyPairAndSeed $key ): void {
		$this->key = $key;
	}

	/**
	 * @param string                                    $path
	 * @param string|null                               $userId
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \Exception
	 */
	public function getJSON( string $path, ?string $userId = null, ?CustomGetJSONOptions $options = null ): JSONResponse {
		$options = $this->buildGetJSONOptions( $options );

		$publicKey = $userId ?? $this->getUserId();
		$dataKey   = deriveDiscoverableFileTweak( $path );

		$options->setHashedDataKeyHex( true );

		return $this->db->getJSON( $publicKey, $dataKey, $options );
	}

	/**
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 * @param array                                     $funcOptions
	 *
	 * @return \Skynet\Options\CustomGetJSONOptions
	 */
	private function buildGetJSONOptions( CustomGetJSONOptions $options = null, array $funcOptions = [] ): CustomGetJSONOptions {
		return $this->buildOptions( Db::DEFAULT_GET_JSON_OPTIONS, CustomGetJSONOptions::class, $options, $funcOptions );
	}

	/**
	 * @return string
	 */
	public function getUserId(): string {
		return $this->key->getPublicKey();
	}

	/**
	 * @param string      $path
	 * @param string|null $userId
	 *
	 * @return string
	 */
	public function getEntryLink( string $path, ?string $userId = null ): string {
		$publicKey = $userId ?? $this->getUserId();
		$dataKey   = deriveDiscoverableFileTweak( $path );

		$options = makeGetEntryOptions( [ 'hashedDataKeyHex' => true ] );

		return getEntryLink( $publicKey, $dataKey, $options );
	}

	/**
	 * @param string                                    $path
	 * @param \stdClass|array                           $json
	 * @param string|null                               $userId
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \Requests_Exception
	 */
	public function setJSON( string $path, $json, ?CustomSetJSONOptions $options = null ): JSONResponse {
		if ( ! is_array( $json ) && ! ( $json instanceof \stdClass ) ) {
			throwValidationError( 'json', $json, 'parameter', 'object or array' );
		}
		$json    = arrayToObject( $json );
		$options = $this->buildSetJSONOptions( $options );

		$publicKey = $this->getUserId();
		$dataKey   = deriveDiscoverableFileTweak( $path );

		$options->setHashedDataKeyHex( true );

		[ $entry, $dataLink ] = $this->db->getOrCreateRegistryEntry( $publicKey, $dataKey, $json, $options );
		$signature = $this->signRegistryEntry( $entry, $path );

		$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );
		$this->getRegistry()->postSignedEntry( $publicKey, $entry, $signature, makeSetEntryOptions( $setEntryOptions ) );

		return new JSONResponse( [ 'data' => $json, 'dataLink' => $dataLink ] );
	}

	/**
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 * @param array                                     $funcOptions
	 *
	 * @return \Skynet\Options\CustomSetJSONOptions
	 */
	private function buildSetJSONOptions( CustomSetJSONOptions $options = null, array $funcOptions = [] ): CustomSetJSONOptions {
		return $this->buildOptions( Db::DEFAULT_GET_JSON_OPTIONS, CustomSetJSONOptions::class, $options, $funcOptions );
	}

	/**
	 * @param \Skynet\Types\RegistryEntry $entry
	 * @param string                      $path
	 *
	 * @return \Skynet\Uint8Array
	 */
	private function signRegistryEntry( RegistryEntry $entry, string $path ): Uint8Array {
		[ 'privateKey' => $privateKey ] = genKeyPairFromSeed( $this->key->getSeed() );

		return signEntry( $privateKey, $entry, true );
	}

	/**
	 * @return \Skynet\Registry
	 */
	public function getRegistry(): Registry {
		return $this->getDb()->getRegistry();
	}

	/**
	 * @return \Skynet\Db
	 */
	public function getDb(): Db {
		return $this->db;
	}


	/**
	 * @param string                                    $path
	 * @param string                                    $dataLink
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return void
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function setDataLink( string $path, string $dataLink, ?CustomSetJSONOptions $options = null): void {
		$options = $this->buildSetJSONOptions( $options );

		$dataKey    = deriveDiscoverableFileTweak( $path );
		$privateKey = $this->key->getPrivateKey();
		$options->setHashedDataKeyHex( true );

		$this->getDb()->setDataLink( $privateKey, $dataKey, $dataLink, $options );
	}

	/**
	 * @param string                                     $path
	 * @param string|null                                $userId
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 *
	 * @return \Skynet\Types\EntryData
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getEntryData( string $path, ?string $userId = null, ?CustomGetEntryOptions $options = null ): EntryData {
		$options = $this->buildGetEntryOptions( $options );

		$publicKey = $userId ?? $this->getUserId();
		$dataKey   = deriveDiscoverableFileTweak( $path );
		$options->setHashedDataKeyHex( true );

		$data = null;
		/** @var \Skynet\Types\SignedRegistryEntry $entry */
		[ 'entry' => $entry ] = $this->getRegistry()->getEntry( $publicKey, $dataKey, $options );
		if ( $entry ) {
			/** @var \Skynet\Types\RegistryEntry $entry */
			$data = $entry->getData();
		}

		return new EntryData( [ 'data' => $data ] );
	}

	/**
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 * @param array                                      $funcOptions
	 *
	 * @return \Skynet\Options\CustomGetEntryOptions
	 */
	private function buildGetEntryOptions( CustomGetEntryOptions $options = null, array $funcOptions = [] ): CustomGetEntryOptions {
		return $this->buildOptions( Registry::DEFAULT_GET_ENTRY_OPTIONS, CustomGetEntryOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                     $path
	 * @param \Skynet\Uint8Array                         $data
	 * @param \Skynet\Options\CustomSetEntryOptions|null $options
	 *
	 * @return \Skynet\Types\EntryData
	 * @throws \Requests_Exception
	 */
	public function setEntryData( string $path, Uint8Array $data, ?CustomSetEntryOptions $options = null ) {
		if ( $data->getMaxLength() > Registry::MAX_ENTRY_LENGTH ) {
			throwValidationError(
				'data',
				$data,
				'parameter',
				sprintf( "'Uint8Array' of length <= %d, was length %d", Registry::MAX_ENTRY_LENGTH, $data->getMaxLength() )
			);
		}

		$options = $this->buildSetEntryOptions( $options );

		$publicKey = $this->getUserId();
		$dataKey   = deriveDiscoverableFileTweak( $path );
		$options->setHashedDataKeyHex( true );
		$getEntryOptions = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );

		$entry     = $this->db->getNextRegistryEntry( $publicKey, $dataKey, $data, makeGetEntryOptions( $getEntryOptions ) );
		$signature = $this->signRegistryEntry( $entry, $path );

		$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );
		$this->getRegistry()->postSignedEntry( $publicKey, $entry, $signature, makeSetEntryOptions( $setEntryOptions ) );

		return new EntryData( [ 'data' => $entry->getData() ] );
	}

	/**
	 * @param \Skynet\Options\CustomSetEntryOptions|null $options
	 * @param array                                      $funcOptions
	 *
	 * @return \Skynet\Options\CustomSetEntryOptions
	 */
	private function buildSetEntryOptions( CustomSetEntryOptions $options = null, array $funcOptions = [] ): CustomSetEntryOptions {
		return $this->buildOptions( Registry::DEFAULT_SET_ENTRY_OPTIONS, CustomSetEntryOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                    $path
	 * @param string|null                               $userId
	 * @param bool                                      $pathHashed
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\EncryptedJSONResponse
	 * @throws \Exception
	 */
	public function getJSONEncrypted( string $path, ?string $userId = null, bool $pathHashed = false, ?CustomGetJSONOptions $options = null ): EncryptedJSONResponse {
		$options = $this->buildGetJSONOptions( $options );
		$options->setHashedDataKeyHex( true );

		$publicKey = $userId ?? $this->getUserId();
		$pathSeed  = $pathHashed ? $path : $this->getEncryptedFileSeed( $path, false );

		$dataKey = deriveEncryptedFileTweak( $pathSeed );
		[ 'data' => $data, 'dataLink' => $dataLink ] = $this->db->getRawBytes( $publicKey, $dataKey, $options );

		if ( null === $data ) {
			return new EncryptedJSONResponse( [ 'data' => null ] );
		}

		$key  = deriveEncryptedFileKeyEntropy( $pathSeed );
		$json = decryptJSONFile( $data, $key );

		return new EncryptedJSONResponse( [ 'data' => $json, 'dataLink' => $dataLink ] );
	}

	/**
	 * @param string $path
	 * @param bool   $isDirectory
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getEncryptedFileSeed( string $path, bool $isDirectory ): string {
		$data = sha512( self::SALT_ENCRYPTED_PATH_SEED ) . hash( 'sha512', $this->key->getSeed() );
		$hash = sha512( $data );

		$rootPathSeed = toHexString( substr( $hash, 0, self::ENCRYPTION_PATH_SEED_LENGTH ) );

		return deriveEncryptedFileSeed( $rootPathSeed, $path, $isDirectory );
	}

	/**
	 * @param string                                    $path
	 * @param \stdClass|array                           $json
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\EncryptedJSONResponse
	 * @throws \Requests_Exception
	 */
	public function setJSONEncrypted( string $path, $json, CustomSetJSONOptions $options = null ): EncryptedJSONResponse {
		if ( ! is_array( $json ) && ! ( $json instanceof \stdClass ) ) {
			throwValidationError( 'json', $json, 'parameter', 'object or array' );
		}

		$json = arrayToObject( $json );


		$options = $this->buildSetJSONOptions( $options );

		$publicKey = $this->getUserId();
		$pathSeed  = $this->getEncryptedFileSeed( $path, false );

		$dataKey = deriveEncryptedFileTweak( $pathSeed );
		$options->setHashedDataKeyHex( true );
		$encryptionKey = deriveEncryptedFileKeyEntropy( $pathSeed );

		$data = encryptJSONFile( $json, makeEncryptedFileMetadata( [ 'version' => self::ENCRYPTED_JSON_RESPONSE_VERSION ] ), $encryptionKey );

		$entry = $this->db->getOrCreateRawBytesRegistryEntry( $publicKey, $dataKey, $data, $options );

		$signature = $this->signEncryptedRegistryEntry( $entry, $path );

		$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );
		$this->getRegistry()->postSignedEntry( $publicKey, $entry, $signature, makeSetEntryOptions( $setEntryOptions ) );

		return new EncryptedJSONResponse( [
			'data'     => $json,
			'dataLink' => formatSkylink( encodeSkylinkBase64( $entry->getData() ) ),
		] );
	}

	/**
	 * @param \Skynet\Types\RegistryEntry $entry
	 * @param string                      $path
	 *
	 * @return \Skynet\Uint8Array
	 * @throws \Exception
	 */
	function signEncryptedRegistryEntry( RegistryEntry $entry, string $path ): Uint8Array {
		$pathSeed = $this->getEncryptedFileSeed( $path, false );
		$dataKey  = deriveEncryptedFileTweak( $pathSeed );
		if ( $entry->getDataKey() !== $dataKey ) {
			throw new \Exception( 'Path does not match the data key in the encrypted registry entry.' );
		}

		return $this->signRegistryEntry( $entry, $path );
	}

	/**
	 * @param string $email
	 * @param string $password
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setPortalLogin( string $email, string $password ) {
		$this->getSkynet()->setPortalLogin( $email, $password );
	}

	/**
	 * @return \Skynet\Skynet
	 */
	public function getSkynet(): Skynet {
		return $this->db->getSkynet();
	}

	public function setPortal( string $portalUrl ) {
		$this->getSkynet()->setPortal( $portalUrl );
	}
}
