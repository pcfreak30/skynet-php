<?php

namespace Skynet;

use BN\BN;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\Utils;
use Skynet\Options\CustomClientOptions;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Options\CustomGetJSONOptions;
use Skynet\Options\CustomSetJSONOptions;
use Skynet\Traits\BaseMethods;
use Skynet\Types\File;
use Skynet\Types\GetFileContentResponse;
use Skynet\Types\JSONResponse;
use Skynet\Types\RawBytesResponse;
use Skynet\Types\RegistryEntry;
use Skynet\Types\SignedRegistryEntry;
use Skynet\Types\UploadRequestResponse;
use stdClass;
use function Skynet\functions\formatting\decodeSkylinkBase64;
use function Skynet\functions\formatting\formatSkylink;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\options\extractOptions;
use function Skynet\functions\options\makeDownloadOptions;
use function Skynet\functions\options\makeGetEntryOptions;
use function Skynet\functions\options\makeSetEntryOptions;
use function Skynet\functions\options\makeUploadOptions;
use function Skynet\functions\sia\decodeSkylink;
use function Skynet\functions\skydb\checkCachedDataLink;
use function Skynet\functions\skydb\parseDataLink;
use function Skynet\functions\strings\hexToString;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;
use function Skynet\functions\url\trimUriPrefix;
use function Skynet\functions\validation\throwValidationError;
use function Skynet\functions\validation\validateHexString;
use function Skynet\functions\validation\validateUint8ArrayLen;
use function Sodium\crypto_sign_publickey_from_secretkey;

/**
 *
 */
class Db {
	use BaseMethods;

	/**
	 *
	 */
	const DEFAULT_GET_JSON_OPTIONS = [
		'apiKey'             => '',
		'customUserAgent'    => '',
		'customCookie'       => '',
		'endpointGetEntry'   => '/skynet/registry',
		'hashedDataKeyHex'   => false,
		'endpointSetEntry'   => '/skynet/registry',
		'endpointDownload'   => '/',
		'download'           => false,
		'path'               => '',
		'range'              => '',
		'responseType'       => '',
		'subdomain'          => '',
		'cachedDataLink'     => '',
		'cachedDownloadLink' => '',
	];

	/**
	 *
	 */
	const DEFAULT_SET_JSON_OPTIONS = [
		'apiKey'              => '',
		'customUserAgent'     => '',
		'customCookie'        => '',
		'endpointUpload'      => '/skynet/skyfile',
		'endpointLargeUpload' => '/skynet/tus',
		'customFilename'      => '',
		'errorPages'          => null,
		'largeFileSize'       => TUS_CHUNK_SIZE,
		'retryDelays'         => DEFAULT_TUS_RETRY_DELAYS,
		'tryFiles'            => null,
	];
	/**
	 * @var \Skynet\Uint8Array|null
	 */
	private static ?Uint8Array $EMPTY_SKYLINK = null;
	/**
	 * @var \Skynet\Registry|null
	 */
	private Registry $registry;
	/**
	 * @var \Skynet\Options\CustomClientOptions|null
	 */
	private CustomClientOptions $options;

	/**
	 * @param \Skynet\Registry|null                    $registry
	 * @param \Skynet\Skynet|null                      $skynet
	 * @param \Skynet\Options\CustomClientOptions|null $options
	 */
	public function __construct( Registry $registry = null, Skynet $skynet = null, ?CustomClientOptions $options = null ) {
		if ( null === $registry ) {
			$registry = new Registry( $skynet, $options );
		}

		if ( null == $options ) {
			$options = new CustomClientOptions();
		}

		if ( null === self::$EMPTY_SKYLINK ) {
			self::$EMPTY_SKYLINK = new Uint8Array( RAW_SKYLINK_SIZE );
		}

		$this->registry = $registry;
		$this->options  = $options;
	}

	/**
	 * @return \Skynet\Uint8Array|null
	 */
	public static function getEmptySkylink(): Uint8Array {
		return clone self::$EMPTY_SKYLINK;
	}

	/**
	 * @return \Skynet\Options\CustomClientOptions|null
	 */
	public function getOptions(): ?CustomClientOptions {
		return $this->options;
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \Exception
	 */
	public function getJSON( string $publicKey, string $dataKey, ?CustomGetJSONOptions $options = null ): PromiseInterface {
		return $this->getJSONAsync( $publicKey, $dataKey, $options )->wait();
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \Exception
	 */
	public function getJSONAsync( string $publicKey, string $dataKey, ?CustomGetJSONOptions $options = null ): PromiseInterface {
		$options = $this->buildGetJSONOptions( $options );

		/** @var RegistryEntry $entry */

		return $this->getRegistryEntryAsync( $publicKey, $dataKey, $options )->then( function ( ?RegistryEntry $entry ) use ( $options, $dataKey ) {
			$resp = new JSONResponse();
			if ( null === $entry ) {
				return $resp;
			}

			[
				'rawDataLink' => $rawDataLink,
				'dataLink'    => $dataLink,
			] = parseDataLink( $entry->getData(), true );

			if ( checkCachedDataLink( $rawDataLink, $options->getCachedDownloadLink() ) ) {
				$resp->setDataLink( $dataLink );

				return $resp;
			}

			$downloadOptions = extractOptions( $options, Skynet::DEFAULT_DOWNLOAD_OPTIONS );

			return $this->getSkynet()->getFileContentAsync( $dataLink, makeDownloadOptions( $downloadOptions ) )->then( function ( ?GetFileContentResponse $response ) use ( $dataKey, $dataLink, &$resp ): JSONResponse {
				[ 'data' => $data ] = $response;
				$data = json_decode( $data );

				if ( ! is_object( $data ) || null === $data ) {
					throw new Exception( sprintf( "File data for the entry at data key %s is not JSON.", $dataKey ) );
				}

				if ( ! ( $data->_data && $data->_v ) ) {
					$resp->setData( $data );
					$resp->setDataLink( $dataLink );
				}

				$actualData = $data->_data;

				if ( ! is_object( $actualData ) || null === $actualData ) {
					throw new Exception( sprintf( "File data '_data' for the entry at data key '%s' is not JSON.", $dataKey ) );
				}

				$resp->setData( $actualData );
				$resp->setDataLink( $dataLink );

				return $resp;
			} );
		} );
	}

	/**
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 * @param array                                     $funcOptions
	 *
	 * @return \Skynet\Options\CustomGetJSONOptions
	 */
	private
	function buildGetJSONOptions(
		CustomGetJSONOptions $options = null, array $funcOptions = []
	): CustomGetJSONOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( self::DEFAULT_GET_JSON_OPTIONS, CustomGetJSONOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                               $publicKey
	 * @param string                               $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions $options
	 *
	 * @return \Skynet\Types\RegistryEntry|null
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getRegistryEntryAsync( string $publicKey, string $dataKey, CustomGetJSONOptions $options ): PromiseInterface {
		if ( $options ) {
			$options = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );
		}

		return $this->registry->getEntryAsync( $publicKey, $dataKey, makeGetEntryOptions( $options ) )->then( function ( SignedRegistryEntry $sentry ) {
			[ 'entry' => $entry ] = $sentry;

			if ( null === $entry || $entry->getData()->compare( self::$EMPTY_SKYLINK ) ) {
				return null;
			}

			return $entry;
		} );
	}

	/**
	 * @return \Skynet\Skynet
	 */
	public function getSkynet(): Skynet {
		return $this->getRegistry()->getSkynet();
	}

	/**
	 * @return \Skynet\Registry|null
	 */
	public function getRegistry(): ?Registry {
		return $this->registry;
	}

	/**
	 * @param string                               $publicKey
	 * @param string                               $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions $options
	 *
	 * @return \Skynet\Types\RegistryEntry|null
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getRegistryEntry( string $publicKey, string $dataKey, CustomGetJSONOptions $options ): ?RegistryEntry {
		return $this->getRegistryEntryAsync( $publicKey, $dataKey, $options )->wait();
	}

	/**
	 * @param string                                    $privateKey
	 * @param string                                    $dataKey
	 * @param                                           $json
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \SodiumException
	 */
	public function setJSON( string $privateKey, string $dataKey, $json, ?CustomSetJSONOptions $options = null ): JSONResponse {
		return $this->setJSONAsync( $privateKey, $dataKey, $json, $options );
	}

	/**
	 * @param string                                    $privateKey
	 * @param string                                    $dataKey
	 * @param                                           $json
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\JSONResponse
	 * @throws \SodiumException
	 */
	public function setJSONAsync( string $privateKey, string $dataKey, $json, ?CustomSetJSONOptions $options = null ) {
		validateHexString( "privateKey", $privateKey, "parameter" );

		$options = $this->buildSetJSONOptions( $options );

		if ( ! is_array( $json ) && ! ( $json instanceof stdClass ) ) {
			throwValidationError( 'json', $json, 'parameter', 'object or array' );
		}

		$publicKey = crypto_sign_publickey_from_secretkey( hexToString( $privateKey ) );

		return $this->getOrCreateRegistryEntry( toHexString( $publicKey ), $dataKey, $json, $options )->then( function ( array $registryEntry ) use ( &$json, $privateKey, &$options ) {
			[ $entry, $dataLink ] = $registryEntry;

			$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );
			$this->registry->setEntry( $privateKey, $entry, makeSetEntryOptions( $setEntryOptions ) );

			return new JSONResponse( [ 'data' => (object) $json, 'dataLink' => formatSkylink( $dataLink ) ] );
		} );
	}

	/**
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 * @param array                                     $funcOptions
	 *
	 * @return \Skynet\Options\CustomSetEntryOptions
	 */
	private
	function buildSetJSONOptions(
		CustomSetJSONOptions $options = null, array $funcOptions = []
	): CustomSetJSONOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( self::DEFAULT_SET_JSON_OPTIONS, CustomSetJSONOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param                                           $json
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return array
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getOrCreateRegistryEntry( string $publicKey, string $dataKey, $json, ?CustomSetJSONOptions $options = null ): array {
		return $this->getOrCreateRegistryEntryAsync( $publicKey, $dataKey, $json, $options )->wait();
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param                                           $json
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return array
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getOrCreateRegistryEntryAsync( string $publicKey, string $dataKey, $json, ?CustomSetJSONOptions $options = null ): PromiseInterface {
		if ( ! is_array( $json ) && ! ( $json instanceof stdClass ) ) {
			throwValidationError( 'json', $json, 'parameter', 'object or array' );
		}
		$options = $this->buildSetJSONOptions( $options );

		$fullData = [ '_data' => $json, '_v' => JSON_RESPONSE_VERSION ];

		$dataKeyHex = $dataKey;
		if ( ! $options->getHashedDataKeyHex() ) {
			$dataKeyHex = toHexString( stringToUint8ArrayUtf8( $dataKey ) );
		}

		$fileData = json_encode( $fullData );
		$fileName = "dk:{$dataKeyHex}";
		$fileType = 'application/json';

		$file = new File( [
			'data'     => Uint8Array::from( $fileData ),
			'fileName' => $fileName,
			'mime'     => $fileType,
		] );

		$uploadOptions = extractOptions( $options, Skynet::DEFAULT_UPLOAD_OPTIONS );

		$uploadPromise = $this->getSkynet()->uploadFileAsync( $file, makeUploadOptions( $uploadOptions ) );

		$getEntryOptions    = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );
		$signedEntryPromise = $this->registry->getEntryAsync( $publicKey, $dataKey, makeGetEntryOptions( $getEntryOptions ) );

		return Utils::all( [ $uploadPromise, $signedEntryPromise ] )->then( function ( $promises ) use ( $dataKey ) {
			[ $skyfile, $signedEntry ] = $promises;

			$revision = $this->getNextRevisionFromEntry( $signedEntry->getEntry() );

			$dataLink = trimUriPrefix( $skyfile->getSkylink(), URI_SKYNET_PREFIX );
			$data     = decodeSkylinkBase64( $dataLink );
			validateUint8ArrayLen( 'data', $data, 'skylink byte array', RAW_SKYLINK_SIZE );
			$entry = new RegistryEntry( $dataKey, $data, new BN( $revision ) );

			return [ $entry, formatSkylink( $dataLink ) ];
		} );
	}

	/**
	 * @param \Skynet\Types\RegistryEntry|null $entry
	 *
	 * @return \BN\BN
	 * @throws \Exception
	 */
	public function getNextRevisionFromEntry( ?RegistryEntry $entry = null ): BN {
		if ( null === $entry ) {
			$revision = new BN( 0 );
		} else {
			$revision = $entry->getRevision()->add( new BN( 1 ) );
		}

		if ( $revision->gt( Registry::getMaxRevision() ) ) {
			throw new Exception( 'Current entry already has maximum allowed revision, could not update the entry' );
		}

		return $revision;
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\RawBytesResponse
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getRawBytes( string $publicKey, string $dataKey, ?CustomGetJSONOptions $options = null ): RawBytesResponse {
		return $this->getRawBytesAsync( $publicKey, $dataKey, $options )->wait();
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Options\CustomGetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\RawBytesResponse
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getRawBytesAsync( string $publicKey, string $dataKey, ?CustomGetJSONOptions $options = null ): PromiseInterface {
		$options = $this->buildGetJSONOptions( $options );

		return $this->getRegistryEntryAsync( $publicKey, $dataKey, $options )->then( function ( ?RegistryEntry $entry ) use ( $options ) {
			if ( null === $entry || $entry->getData()->compare( self::$EMPTY_SKYLINK ) ) {
				return new RawBytesResponse( [ 'data' => null, 'dataLink' => null ] );
			}

			[ 'rawDataLink' => $rawDataLink, 'dataLink' => $dataLink ] = parseDataLink( $entry->getData(), false );

			if ( checkCachedDataLink( $rawDataLink, $options->getCachedDataLink() ) ) {
				return new RawBytesResponse( [ 'data' => null, 'dataLink' => $dataLink ] );
			}

			$downloadOptions                 = extractOptions( $options, Skynet::DEFAULT_DOWNLOAD_OPTIONS );
			$downloadOptions['responseType'] = 'arraybuffer';

			return $this->getSkynet()->getFileContentAsync( $dataLink, makeDownloadOptions( $downloadOptions ) )->then( function ( GetFileContentResponse $response ) use ( $dataLink ) {
				[ 'data' => $buffer ] = $response;

				return new RawBytesResponse( [ 'data' => Uint8Array::from( $buffer ), 'dataLink' => $dataLink ] );
			} );

		} );
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Uint8Array                        $data
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\RegistryEntry
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getOrCreateRawBytesRegistryEntry( string $publicKey, string $dataKey, Uint8Array $data, ?CustomSetJSONOptions $options = null ): RegistryEntry {
		return $this->getOrCreateRawBytesRegistryEntryAsync( $publicKey, $dataKey, $data, $options )->wait();
	}

	/**
	 * @param string                                    $publicKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Uint8Array                        $data
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \Skynet\Types\RegistryEntry
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getOrCreateRawBytesRegistryEntryAsync( string $publicKey, string $dataKey, Uint8Array $data, ?CustomSetJSONOptions $options = null ): PromiseInterface {
		$options = $this->buildSetJSONOptions( $options );

		$dataKeyHex = $dataKey;
		if ( ! $options->getHashedDataKeyHex() ) {
			$dataKeyHex = toHexString( $dataKey );
		}

		$file = new File( [ 'data' => $data, 'fileName' => "dk:{$dataKeyHex}", 'mime' => 'application/octet-stream' ] );

		$uploadOptions = extractOptions( $options, Skynet::DEFAULT_UPLOAD_OPTIONS );

		$skyfilePromise = $this->getSkynet()->uploadFileAsync( $file, makeUploadOptions( $uploadOptions ) );

		$getEntryOptions    = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );
		$signedEntryPromise = $this->registry->getEntryAsync( $publicKey, $dataKey, makeGetEntryOptions( $getEntryOptions ) );

		return Utils::all( [ $skyfilePromise, $signedEntryPromise ] )->then( function ( $promises ) use ( $dataKey ) {
			[ $skyfile, $signedEntry ] = $promises;

			$revision = $this->getNextRevisionFromEntry( $signedEntry->getEntry() );

			$dataLink    = trimUriPrefix( $skyfile->getSkylink(), URI_SKYNET_PREFIX );
			$rawDataLink = decodeSkylinkBase64( $dataLink );
			validateUint8ArrayLen( 'rawDataLink', $rawDataLink, 'skylink byte array', RAW_SKYLINK_SIZE );

			return new RegistryEntry( $dataKey, $rawDataLink, $revision );
		} );
	}

	/**
	 * @param string                                    $privateKey
	 * @param string                                    $dataKey
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return void
	 * @throws \SodiumException
	 */
	public function deleteJSON( string $privateKey, string $dataKey, ?CustomSetJSONOptions $options = null ): void {
		validateHexString( 'privateKey', $privateKey, 'parameter' );

		$options = $this->buildSetJSONOptions( $options );

		$publicKey       = toHexString( crypto_sign_publickey_from_secretkey( hexToString( $privateKey ) ) );
		$getEntryOptions = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );

		$entry = $this->getNextRegistryEntry( $publicKey, $dataKey, new Uint8Array( RAW_SKYLINK_SIZE ), makeGetEntryOptions( $getEntryOptions ) );

		$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );
		$this->registry->setEntry( $privateKey, $entry, makeSetEntryOptions( $setEntryOptions ) );
	}

	/**
	 * @param string                                     $publicKey
	 * @param string                                     $dataKey
	 * @param \Skynet\Uint8Array                         $data
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 *
	 * @return \Skynet\Types\RegistryEntry
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getNextRegistryEntry( string $publicKey, string $dataKey, Uint8Array $data, CustomGetEntryOptions $options = null ): RegistryEntry {
	}

	/**
	 * @param string                                    $privateKey
	 * @param string                                    $dataKey
	 * @param string                                    $dataLink
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return void
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function setDataLink( string $privateKey, string $dataKey, string $dataLink, ?CustomSetJSONOptions $options = null ): void {
		$this->setDataLinkAsync( $privateKey, $dataKey, $dataLink, $options )->wait();
	}


	/**
	 * @param string                                    $privateKey
	 * @param string                                    $dataKey
	 * @param string                                    $dataLink
	 * @param \Skynet\Options\CustomSetJSONOptions|null $options
	 *
	 * @return \GuzzleHttp\Promise\PromiseInterface
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function setDataLinkAsync( string $privateKey, string $dataKey, string $dataLink, ?CustomSetJSONOptions $options = null ): PromiseInterface {
		validateHexString( 'privateKey', $privateKey, 'parameter' );

		$options = $this->buildSetJSONOptions( $options );

		$publicKey = toHexString( crypto_sign_publickey_from_secretkey( hexToString( $privateKey ) ) );

		$getEntryOptions = extractOptions( $options, Registry::DEFAULT_GET_ENTRY_OPTIONS );

		return $this->getNextRegistryEntryAsync( $publicKey, $dataKey, decodeSkylink( $dataLink ), makeGetEntryOptions( $getEntryOptions ) )->then( function ( RegistryEntry $entry ) use ( $privateKey, $dataKey, $dataLink, $options ) {
			$setEntryOptions = extractOptions( $options, Registry::DEFAULT_SET_ENTRY_OPTIONS );

			return $this->registry->setEntryAsync( $privateKey, $entry, makeSetEntryOptions( $setEntryOptions ) )->otherwise( function ( $e ) use ( $privateKey, $dataKey, $dataLink, $options ) {
				if ( $e instanceof ClientException ) {
					if ( 400 !== $e->getResponse()->getStatusCode() ) {
						throw $e;
					}
				}

				return $this->setDataLinkAsync( $privateKey, $dataKey, $dataLink, $options );
			} );
		} );
	}

	/**
	 * @param string                                     $publicKey
	 * @param string                                     $dataKey
	 * @param \Skynet\Uint8Array                         $data
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 *
	 * @return \GuzzleHttp\Promise\PromiseInterface
	 * @throws \Requests_Exception
	 * @throws \SodiumException
	 */
	public function getNextRegistryEntryAsync( string $publicKey, string $dataKey, Uint8Array $data, CustomGetEntryOptions $options = null ): PromiseInterface {
		$options = $this->buildGetEntryOptions( $options );

		return $this->registry->getEntryAsync( $publicKey, $dataKey, $options )->then( function ( SignedRegistryEntry $signedEntry ) use ( $data, $dataKey ) {
			$revision = $this->getNextRevisionFromEntry( $signedEntry->getEntry() );

			return new RegistryEntry( $dataKey, $data, $revision );
		} );
	}
}
