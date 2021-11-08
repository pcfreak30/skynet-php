<?php

namespace Skynet;

use Exception;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Cookie\SetCookie;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\MimeType;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use Skynet\Options\CustomClientOptions;
use Skynet\Options\CustomDownloadOptions;
use Skynet\Options\CustomGetMetadataOptions;
use Skynet\Options\CustomHnsDownloadOptions;
use Skynet\Options\CustomHnsResolveOptions;
use Skynet\Options\CustomPinOptions;
use Skynet\Options\CustomUploadOptions;
use Skynet\Options\Request;
use Skynet\Traits\BaseMethods;
use Skynet\Types\File;
use Skynet\Types\GetFileContentResponse;
use Skynet\Types\GetMetadataResponse;
use Skynet\Types\PinResponse;
use Skynet\Types\ResolveHnsResponse;
use Skynet\Types\UploadRequestResponse;
use TusPhp\Tus\Client;
use function Skynet\functions\formatting\formatSkylink;
use function Skynet\functions\options\makeDownloadOptions;
use function Skynet\functions\options\makeGetEntryOptions;
use function Skynet\functions\options\makeGetMetadataOptions;
use function Skynet\functions\options\makeOptions;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\registry\getEntryLink;
use function Skynet\functions\registry\validateRegistryProofResponse;
use function Skynet\functions\skylinks\buildDownloadQuery;
use function Skynet\functions\skylinks\getSkylinkUrlForPortal;
use function Skynet\functions\skylinks\parseSkylink;
use function Skynet\functions\upload\generate_uuid4;
use function Skynet\functions\upload\validateLargeUploadResponse;
use function Skynet\functions\upload\validateUploadResponse;
use function Skynet\functions\url\add_query_arg;
use function Skynet\functions\url\addSubdomain;
use function Skynet\functions\url\makeUrl;
use function Skynet\functions\url\trailingslashit;
use function Skynet\functions\url\trimUriPrefix;
use function Skynet\functions\validation\throwValidationError;
use function Skynet\functions\validation\validateObject;
use function Skynet\functions\validation\validateSkylinkString;
use function Skynet\functions\validation\validateString;

/**
 *
 */
class Skynet {
	use BaseMethods;

	/**
	 *
	 */
	const DEFAULT_BASE_OPTIONS = [
		'apiKey'          => '',
		'customUserAgent' => '',
		'customCookie'    => '',
	];
	/**
	 *
	 */
	const DEFAULT_DOWNLOAD_OPTIONS = [
		'apiKey'           => '',
		'customUserAgent'  => '',
		'customCookie'     => '',
		'endpointDownload' => '/',
		'download'         => false,
		'path'             => '',
		'range'            => '',
		'responseType'     => '',
		'subdomain'        => '',

	];

	/**
	 *
	 */
	const DEFAULT_DOWNLOAD_HNS_OPTIONS = [
		'apiKey'              => '',
		'customUserAgent'     => '',
		'customCookie'        => '',
		'endpointDownload'    => '/',
		'download'            => false,
		'path'                => '',
		'range'               => '',
		'responseType'        => '',
		'endpointDownloadHns' => 'hns',
		'hnsSubdomain'        => 'hns',
		'subdomain'           => true,

	];
	/**
	 *
	 */
	const DEFAULT_RESOLVE_HNS_OPTIONS = [
		'apiKey'             => '',
		'customUserAgent'    => '',
		'customCookie'       => '',
		'endpointResolveHns' => 'hnsres',
	];
	/**
	 *
	 */
	const DEFAULT_GET_METADATA_OPTIONS = [
		'apiKey'              => '',
		'customUserAgent'     => '',
		'customCookie'        => '',
		'endpointGetMetadata' => "/skynet/metadata",
	];
	/**
	 *
	 */
	const DEFAULT_PIN_OPTIONS = [
		'apiKey'          => '',
		'customUserAgent' => '',
		'customCookie'    => '',
		'endpointPin'     => '/skynet/pin',
	];
	/**
	 *
	 */
	const DEFAULT_UPLOAD_OPTIONS = [
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
	 * @var string
	 */
	private string $initialPortalUrl;
	/**
	 * @var string
	 */
	private string $customPortalUrl;
	/**
	 * @var string
	 */
	private string $resolvedPortalUrl;
	/**
	 * @var \Skynet\Options\CustomClientOptions|null
	 */
	private CustomClientOptions $options;
	/**
	 * @var string|null
	 */
	private ?string $sessionKey = null;
	/**
	 * @var string
	 */
	private string $portalAccountUrl = 'https://account.siasky.net';
	/**
	 * @var string
	 */
	private string $portalLoginEmail;
	/**
	 * @var string
	 */
	private string $portalLoginPassword;

	/**
	 * @param null                                $initialPortalUrl
	 * @param \Skynet\Options\CustomClientOptions $options
	 */
	public function __construct( $initialPortalUrl = null, ?CustomClientOptions $options = null ) {
		if ( null === $initialPortalUrl ) {
			$initialPortalUrl = 'https://siasky.net';
		} else {
			$this->customPortalUrl = $initialPortalUrl;
		}

		if ( null !== $options ) {
			$options = makeOptions( CustomClientOptions::class, mergeOptions( self::DEFAULT_BASE_OPTIONS, $options ) );
		} else {
			$options = new CustomClientOptions( self::DEFAULT_BASE_OPTIONS );
		}

		$this->initialPortalUrl = $initialPortalUrl;
		$this->options          = $options;
	}

	/**
	 * @param string $customPortalUrl
	 */
	public function setPortal( string $portalUrl ): void {
		$this->customPortalUrl = $portalUrl;
	}

	/**
	 * @return string
	 */
	public function getInitialPortalUrl() {
		return $this->initialPortalUrl;
	}

	/**
	 * @return \Skynet\Options\CustomClientOptions
	 */
	public function getOptions(): CustomClientOptions {
		return $this->options;
	}

	/**
	 * @param \Skynet\Options\CustomClientOptions $options
	 */
	public function setOptions( CustomClientOptions $options ): void {
		$this->options = $options;
	}

	/**
	 * @return string
	 */
	public function getResolvedPortalUrl(): string {
		return $this->resolvedPortalUrl;
	}

	/**
	 * @param string                              $skylinkUrl
	 * @param \Skynet\Options\CustomClientOptions $options
	 *
	 * @return mixed
	 */
	public function downloadFile( string $skylinkUrl, ?CustomDownloadOptions $options = null, ?Request $reqOptions = null ): Response {

		$options    = $this->buildDownloadOptions( $options, [ 'download' => true ] );
		$reqOptions = $this->buildRequestOptions( $reqOptions ? $reqOptions->toArray() : null,
			[
				'method' => 'GET',
				'endpointPath' => $options->getEndpointDownload(),
			] );

		$reqOptions->setUrl( $this->getSkylinkUrl( $skylinkUrl, $options ) );

		return $this->executeRequest( $reqOptions );
	}

	/**
	 * @param \Skynet\Options\CustomDownloadOptions|null $options
	 * @param array                                      $funcOptions
	 *
	 * @return \Skynet\Options\CustomDownloadOptions
	 */
	private function buildDownloadOptions( CustomDownloadOptions $options = null, array $funcOptions = [] ): CustomDownloadOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( self::DEFAULT_DOWNLOAD_OPTIONS, CustomDownloadOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                     $skylinkUrl
	 * @param \Skynet\Options\CustomDownloadOptions|null $options
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getSkylinkUrl( string $skylinkUrl, ?CustomDownloadOptions $options = null ) {
		$options = $this->buildDownloadOptions( $options );

		$portalUrl = $this->getPortalUrl();

		return getSkylinkUrlForPortal( $portalUrl, $skylinkUrl, $options );
	}

	/**
	 * @return string
	 */
	public function getPortalUrl() {
		if ( isset( $this->customPortalUrl ) ) {
			return $this->customPortalUrl;
		}

		$this->initPortalUrl();

		return $this->resolvedPortalUrl;
	}

	/**
	 * @return void
	 * @throws \Exception
	 */
	private function initPortalUrl() {
		if ( isset( $this->customPortalUrl ) ) {
			return;
		}

		if ( ! isset( $this->resolvedPortalUrl ) || null === $this->resolvedPortalUrl ) {
			$this->resolvedPortalUrl = $this->resolvePortalUrl();
		}

	}

	/**
	 * @return string
	 * @throws \Requests_Exception
	 */
	private function resolvePortalUrl() {
		$options = mergeOptions(
			$this->options->toArray(),
			[
				'method'       => 'GET',
				'url'          => $this->initialPortalUrl,
				'endpointPath' => '/',
			]
		);

		$response = $this->executeRequest( $this->buildRequestOptions( $options ) );
		if ( ! $response->getHeaders() || empty( $response->getHeaders() ) ) {
			throw new Exception( "Did not get 'headers' in response despite a successful request. Please try again and report this issue to the devs if it persists." );
		}

		$portalUrl = $response->getHeader( 'skynet-portal-api' )[0] ?? null;
		if ( ! $portalUrl ) {
			throw new Exception( 'Could not get portal URL for the given portal' );
		}

		return rtrim( $portalUrl, '/' );
	}

	/**
	 * @param string                                        $skylinkUrl
	 * @param \Skynet\Options\CustomHnsDownloadOptions|null $options
	 *
	 * @return \GuzzleHttp\Psr7\Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function downloadFileHns( string $skylinkUrl, ?CustomHnsDownloadOptions $options = null ): Response {
		$options = $this->buildHnsDownloadOptions( $options, [ 'download' => true ] );

		$url = $this->getHnsUrl( $skylinkUrl, $options );

		return $this->getHttpClient()->request( 'get', $url );
	}

	/**
	 * @param \Skynet\Options\CustomHnsDownloadOptions|null $options
	 * @param array                                         $funcOptions
	 *
	 * @return \Skynet\Options\CustomHnsDownloadOptions
	 */
	private function buildHnsDownloadOptions( ?CustomHnsDownloadOptions $options = null, array $funcOptions = [] ): CustomHnsDownloadOptions {
		/** @noinspection PhpParamsInspection */
		return $this->buildOptions( self::DEFAULT_DOWNLOAD_HNS_OPTIONS, CustomHnsDownloadOptions::class, $options, $funcOptions );
	}

	/**
	 * @param string                                        $domain
	 * @param \Skynet\Options\CustomHnsDownloadOptions|null $options
	 *
	 * @return string
	 */
	public function getHnsUrl( string $domain, ?CustomHnsDownloadOptions $options = null ): string {
		$options = $this->buildHnsDownloadOptions( $options );
		$query   = buildDownloadQuery( $options->getDownload() );

		$domain    = trimUriPrefix( $domain, URI_HANDSHAKE_PREFIX );
		$portalUrl = $this->getPortalUrl();
		if ( $options->isSubdomain() ) {
			$url = addSubdomain( addSubdomain( $portalUrl, $options->getHnsSubdomain() ), $domain );
		} else {
			$url = makeUrl( $portalUrl, $options->getEndpointDownloadHns(), $domain );
		}

		$urlParts = parse_url( $url );
		if ( ! isset( $urlParts['path'] ) || '' === $urlParts['path'] ) {
			$urlParts['path'] = '/';
			$url              = http_build_url( $urlParts );
		}

		return add_query_arg( $query, $url );
	}

	/**
	 * @param string                                        $skylinkUrl
	 * @param \Skynet\Options\CustomGetMetadataOptions|null $options
	 *
	 * @return \Skynet\Types\GetMetadataResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \JsonException
	 */
	public function getMetadata( string $skylinkUrl, ?CustomGetMetadataOptions $options = null ) {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		$options = mergeOptions(
			self::DEFAULT_GET_METADATA_OPTIONS,
			$this->options,
			$options,
		);
		$options = makeGetMetadataOptions( $options );
		/** @noinspection PhpParamsInspection */
		$path = parseSkylink( $skylinkUrl, makeParseSkylinkOptions( [ 'onlyPath' => true ] ) );
		if ( $path ) {
			throw new Exception( 'Skylink string should not contain a path' );
		}

		$getSkylinkUrlOpts = makeDownloadOptions( [ 'endpointDownload' => $options->getEndpointGetMetadata() ] );

		$url = $this->getSkylinkUrl( $skylinkUrl, $getSkylinkUrlOpts );

		$response = $this->executeRequest( $this->buildRequestOptions(
			$options->toArray(), [
			'endpointPath' => $options->getEndpointGetMetadata(),
			'method'       => 'GET',
			'url'          => $url,
		] ) );

		$inputSkylink = parseSkylink( $skylinkUrl );

		$response->getBody()->rewind();
		$body = json_decode( $response->getBody()->getContents() );

		try {
			if ( ! $body && ! is_array( $body ) ) {
				throw new Exception( "'response->body' field missing" );
			}

			if ( ! $response->getHeaders() || empty( $response->getHeaders() ) ) {
				throw new Exception( "'response->headers' field missing" );
			}

			$portalUrl = $response->getHeader( 'skynet-portal-api' )[0] ?? null;
			if ( ! $portalUrl ) {
				throw new Exception( "'skynet-portal-api' header missing" );
			}

			validateString( 'response->headers("skynet-portal-api")', $portalUrl, "getMetadata response header" );

			$skylink = $response->getHeader( 'skynet-skylink' ) [0] ?? null;
			if ( ! $skylink ) {
				throw new Exception( "'skynet-skylink' header missing" );
			}

			validateSkylinkString( 'response->headers("skynet-skylink")', $skylink, "getMetadata response header" );
		} catch ( Exception $e ) {
			throw new Exception( sprintf( "Metadata response invalid despite a successful request. Please try again and report this issue to the devs if it persists. %s", $e->getMessage() ) );
		}

		validateRegistryProofResponse( $inputSkylink, $skylink, $response->getHeader( 'skynet-proof' )[0] ?? null );

		$response->getBody()->rewind();
		$metadata = (object) json_decode( $response->getBody()->getContents() );

		$portalUrl = $response->getHeader( 'skynet-portal-api' )[0] ?? null;
		$skylink   = formatSkylink( $response->getHeader( 'skynet-skylink' )[0] ?? null );

		return new GetMetadataResponse( [ 'metadata' => $metadata, 'portalUrl' => $portalUrl, 'skylink' => $skylink ] );
	}

	/**
	 * @param string                                     $skylinkUrl
	 * @param \Skynet\Options\CustomDownloadOptions|null $options
	 *
	 * @return \Skynet\Types\GetFileContentResponse
	 * @throws \Exception
	 */
	public function getFileContent( string $skylinkUrl, ?CustomDownloadOptions $options = null ): GetFileContentResponse {
		$options = $this->buildDownloadOptions( $options );

		$response = $this->getFileContentRequest( $skylinkUrl, $options );

		$inputSkylink = parseSkylink( $skylinkUrl );

		$this->validateGetFileContentResponse( $response, $inputSkylink );

		return $this->extractGetFileContentResponse( $response );
	}

	/**
	 * @param string                                     $skylinkUrl
	 * @param \Skynet\Options\CustomDownloadOptions|null $options
	 *
	 * @return \GuzzleHttp\Psr7\Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function getFileContentRequest( string $skylinkUrl, ?CustomDownloadOptions $options = null ) {
		$options = $this->buildDownloadOptions( $options );

		$url = $this->getSkylinkUrl( $skylinkUrl, $options );

		$headers = $this->buildGetFileContentHeaders( $options->getRange() );

		return $this->executeRequest( $this->buildRequestOptions( [
			$options,
			'endpointPath' => $options->getEndpointDownload(),
			'method'       => 'GET',
			'url'          => $url,
			'headers'      => $headers,
		] ) );
	}

	/**
	 * @param string $range
	 *
	 * @return array
	 */
	private function buildGetFileContentHeaders( string $range ): array {
		$headers = [];

		if ( $range ) {
			$headers['range'] = $range;
		}

		return $headers;
	}

	/**
	 * @param \GuzzleHttp\Psr7\Response $response
	 * @param string                    $inputSkylink
	 *
	 * @return void
	 * @throws \Exception
	 */
	private function validateGetFileContentResponse( Response $response, string $inputSkylink ): void {
		$response->getBody()->rewind();
		$body = $response->getBody()->getContents();

		try {
			if ( null === $body ) {
				throw new Exception( "'response->data' field missing" );
			}
			if ( ! $response->getHeaders() || empty( $response->getHeaders() ) ) {
				throw new Exception( "'response->headers' field missing" );
			}

			$contentType = $response->getHeader( 'content-type' )[0] ?? null;
			if ( ! $contentType ) {
				throw new Exception( "'content-type' header missing" );
			}
			validateString( 'response->headers("content-type")', $contentType, "getFileContent response header" );


			$portalUrl = $response->getHeader( 'skynet-portal-api' )[0] ?? null;
			if ( ! $portalUrl ) {
				throw new Exception( "'skynet-portal-api' header missing" );
			}
			validateString( 'response->headers("skynet-portal-api")', $portalUrl, "getFileContent response header" );


			$skylink = $response->getHeader( 'skynet-skylink' )[0] ?? null;
			if ( ! $skylink ) {
				throw new Exception( "'skynet-skylink' header missing" );
			}

			validateSkylinkString( 'response->headers("skynet-skylink")', $skylink, "getFileContent response header" );

			$proof = $response->getHeader( 'skynet-proof' )[0] ?? null;
			validateRegistryProofResponse( $inputSkylink, $skylink, $proof );

		} catch ( Exception $e ) {
			throw new Exception( sprintf( 'File content response invalid despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() ) );
		}
	}

	/**
	 * @param \GuzzleHttp\Psr7\Response $response
	 *
	 * @return \Skynet\Types\GetFileContentResponse
	 */
	private function extractGetFileContentResponse( Response $response ): GetFileContentResponse {
		$contentType = $response->getHeader( 'content-type' )[0] ?? null;
		$portalUrl   = $response->getHeader( "skynet-portal-api" )[0] ?? null;
		$skylink     = formatSkylink( $response->getHeader( 'skynet-skylink' )[0] ?? null );

		$response->getBody()->rewind();

		return new GetFileContentResponse( [
			'data'        => $response->getBody()->getContents(),
			'contentType' => $contentType,
			'portalUrl'   => $portalUrl,
			'skylink'     => $skylink,
		] );
	}

	/**
	 * @param string                                        $domain
	 * @param \Skynet\Options\CustomHnsDownloadOptions|null $options
	 *
	 * @return \Skynet\Types\GetFileContentResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function getFileContentHns( string $domain, ?CustomHnsDownloadOptions $options = null ) {
		$options = $this->buildHnsDownloadOptions( $options );

		$url     = $this->getHnsUrl( $domain, $options );
		$headers = $this->buildGetFileContentHeaders( $options->getRange() );

		$response = $this->executeRequest( $this->buildRequestOptions( [
			$options,
			'endpointPath' => $options->getEndpointDownload(),
			'method'       => 'GET',
			'url'          => $url,
			'headers'      => $headers,
		] ) );

		[ 'skylink' => $inputSkylink ] = $this->resolveHns( $domain );

		$this->validateGetFileContentResponse( $response, $inputSkylink );

		return $this->extractGetFileContentResponse( $response );
	}

	/**
	 * @param string                                       $domain
	 * @param \Skynet\Options\CustomHnsResolveOptions|null $options
	 *
	 * @return \Skynet\Types\ResolveHnsResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function resolveHns( string $domain, ?CustomHnsResolveOptions $options = null ) {
		/** @var CustomHnsResolveOptions $options */
		$options = $this->buildHnResolveOptions( $options );

		$url = $this->getHnsresUrl( $domain, $options );


		/** @var Response $response */
		$response = $this->executeRequest( $this->buildRequestOptions( [
			$options,
			'endpointPath' => $options->getEndpointResolveHns(),
			'method'       => 'GET',
			'url'          => $url,
		] ) );

		try {
			$response->getBody()->rewind();
			$body = $response->getBody()->getContents();
			if ( ! $body ) {
				throw new Exception( '\'response->body\' field missing' );
			}
			$body = json_decode( $body );

			if ( isset( $body->skylink ) ) {
				validateSkylinkString( "response->body.skylink", $body->skylink, "resolveHns response field" );
			} elseif ( isset( $body->registry ) ) {
				validateObject( "response->body.registry", $body->registry, "resolveHns response field" );
				validateString( "response->body.registry.publickey", $body->registry->publickey, "resolveHns response field" );
				validateString( "response->body.registry.datakey", $body->registry->datakey, "resolveHns response field" );
			} else {
				throwValidationError(
					"response->body",
					$body,
					"response body object",
					"object containing skylink or registry field"
				);
			}
		} catch ( Exception $e ) {
			throw new Exception( sprintf( 'Did not get a complete resolve HNS response despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() ) );
		}

		$hnsResp = new ResolveHnsResponse();

		if ( isset( $body->skylink ) ) {
			$hnsResp->set( [ 'data' => $body, 'skylink' => $body->skylink ] );
		} else {
			$entryLink = getEntryLink( $body->registry->publickey, $body->registry->datakey, makeGetEntryOptions( [ 'hashedDataKeyHex' => true ] ) );
			$hnsResp->set( [ 'data' => $body, 'skylink' => $entryLink ] );
		}

		return $hnsResp;
	}

	/**
	 * @param \Skynet\Options\CustomHnsResolveOptions|null $options
	 *
	 * @return \Skynet\Entity
	 */
	private function buildHnResolveOptions( ?CustomHnsResolveOptions $options = null ) {
		return $this->buildOptions( self::DEFAULT_RESOLVE_HNS_OPTIONS, CustomHnsResolveOptions::class, $options );
	}

	/**
	 * @param string                                       $domain
	 * @param \Skynet\Options\CustomHnsResolveOptions|null $options
	 *
	 * @return string
	 */
	public function getHnsresUrl( string $domain, ?CustomHnsResolveOptions $options = null ): string {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		$options = $this->buildHnResolveOptions( $options );

		$domain    = trimUriPrefix( $domain, URI_HANDSHAKE_PREFIX );
		$portalUrl = $this->getPortalUrl();

		return makeUrl( $portalUrl, $options->getEndpointResolveHns(), $domain );
	}

	/**
	 * @param string                                $skylinkUrl
	 * @param \Skynet\Options\CustomPinOptions|null $options
	 *
	 * @return \Skynet\Types\PinResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function pinSkylink( string $skylinkUrl, ?CustomPinOptions $options = null ): PinResponse {
		$skylink = validateSkylinkString( 'skylinkUrl', $skylinkUrl, 'parameter' );
		$options = $this->buildPinOptions( $options );

		$path = parseSkylink( $skylinkUrl, makeParseSkylinkOptions( [ 'onlyPath' => true ] ) );
		if ( $path ) {
			throw new Exception( 'Skylink string should not contain a path' );
		}

		/** @var CustomPinOptions $options */
		$response = $this->executeRequest( $this->buildRequestOptions( [
			$options,
			'endpointPath' => $options->getEndpointPin(),
			'method'       => 'POST',
			'extraPath'    => $skylink,
		] ) );

		try {
			if ( ! $response->getHeaders() || empty( $response->getHeaders() ) ) {
				throw new Exception( 'response->headers field missing' );
			}
			validateString( 'response->headers("skynet-skylink")', $response->getHeader( "skynet-skylink" )[0] ?? null, "pin response field" );
		} catch ( Exception $e ) {
			throw new Exception( sprintf( 'Did not get a complete pin response despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() ) );
		}

		$returnedSkylink = $response->getHeader( "skynet-skylink" )[0] ?? null;
		$returnedSkylink = formatSkylink( $returnedSkylink );

		return new PinResponse( [ 'skylink' => $returnedSkylink ] );
	}

	/**
	 * @param \Skynet\Options\CustomPinOptions|null $options
	 *
	 * @return \Skynet\Entity
	 */
	private function buildPinOptions( ?CustomPinOptions $options = null ) {
		return $this->buildOptions( self::DEFAULT_PIN_OPTIONS, CustomPinOptions::class, $options );
	}

	/**
	 * @return \Skynet\Db
	 */
	public function getNewDb(): Db {
		return new Db( $this->getNewRegistry() );
	}

	/**
	 * @return \Skynet\Registry
	 */
	public function getNewRegistry(): Registry {
		return new Registry( $this, $this->options );
	}

	/**
	 * @param \Skynet\Types\File                       $file
	 * @param \Skynet\Options\CustomUploadOptions|null $options
	 *
	 * @return \Skynet\Types\UploadRequestResponse
	 */
	public function uploadFile( File $file, CustomUploadOptions $options = null ): UploadRequestResponse {
		$options = $this->buildUploadOptions( $options );

		if ( $file->getFileSize() < $options->getLargeFileSize() ) {
			return $this->uploadSmallFile( $file, $options );
		}

		return $this->uploadLargeFile( $file, $options );
	}

	/**
	 * @param \Skynet\Options\CustomUploadOptions|null $options
	 *
	 * @return \Skynet\Options\CustomUploadOptions
	 */
	private function buildUploadOptions( CustomUploadOptions $options = null ): CustomUploadOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( self::DEFAULT_UPLOAD_OPTIONS, CustomUploadOptions::class, $options );
	}

	/**
	 * @param \Skynet\Types\File                  $file
	 * @param \Skynet\Options\CustomUploadOptions $options
	 *
	 * @return \Skynet\Types\UploadRequestResponse
	 * @throws \Exception
	 */
	private function uploadSmallFile( File $file, CustomUploadOptions $options ): UploadRequestResponse {
		$response = $this->uploadSmallFileRequest( $file, $options );

		validateUploadResponse( $response );

		$response->getBody()->rewind();

		$skylink = formatSkylink( json_decode( $response->getBody()->getContents() )->skylink );

		return new UploadRequestResponse( [ 'skylink' => $skylink ] );
	}

	/**
	 * @param \Skynet\Types\File                  $file
	 * @param \Skynet\Options\CustomUploadOptions $options
	 *
	 * @return \GuzzleHttp\Psr7\Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function uploadSmallFileRequest( File $file, CustomUploadOptions $options ): Response {
		$options = $this->buildUploadOptions( $options );

		$requestOpts = $options->getExtraOptions();

		$fileHeaders = [];

		if ( ! $file->getMime() ) {
			$mime = MimeType::fromFilename( $file->getFileName() );
			if ( ! $mime ) {
				$mime = 'application/octet-stream';
			}
			$file->setMime( $mime );
		}

		$fileHeaders['Content-Type'] = $file->getMime();

		if ( $file->getData() ) {
			$formDataItem = [
				'name'     => PORTAL_FILE_FIELD_NAME,
				'contents' => $file->getData()->toString(),
				'filename' => ! empty( $options->getCustomFilename() ) ? $options->getCustomFilename() : $file->getFileName(),
				'headers'  => $fileHeaders,
			];

		} else {
			$formDataItem = [
				'name'     => PORTAL_FILE_FIELD_NAME,
				'contents' => $file->getFileName(),
				'filename' => $options->getCustomFilename(),
				'headers'  => $fileHeaders,
			];
			if ( $file->getStream() ) {
				$formDataItem['contents'] = $file->getStream();
				$formDataItem['filename'] = empty( $options->getCustomFilename() ) ? $file->getFileName() : $options->getCustomFilename();
			}
		}

		$requestOpts['multipart'] = [ $formDataItem ];

		return $this->executeRequest( $this->buildRequestOptions( $options->toArray(), [
			'options'      => $requestOpts,
			'endpointPath' => $options->getEndpointUpload(),
			'method'       => 'POST',
		] ) );

	}

	/**
	 * @param \Skynet\Types\File                       $file
	 * @param \Skynet\Options\CustomUploadOptions|null $options
	 *
	 * @return \Skynet\Types\UploadRequestResponse
	 * @throws \Exception
	 */
	private function uploadLargeFile( File $file, ?CustomUploadOptions $options = null ) {
		$response = $this->uploadLargeFileRequest( $file, $options );

		validateLargeUploadResponse( $response );

		$skylink = formatSkylink( $response->getHeader( "skynet-skylink" )[0] ?? null );

		return new UploadRequestResponse( [ 'slylink' => $skylink ] );
	}

	/**
	 * @param \Skynet\Types\File                  $file
	 * @param \Skynet\Options\CustomUploadOptions $options
	 *
	 * @return string|null
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \ReflectionException
	 * @throws \TusPhp\Exception\ConnectionException
	 * @throws \TusPhp\Exception\TusException
	 */
	private function uploadLargeFileRequest( File $file, CustomUploadOptions $options ) {
		$options = $this->buildUploadOptions( $options );

		$url     = $this->buildRequestUrl( $options->getEndpointLargeUpload() );
		$headers = $this->buildRequestHeaders( [], $options->getCustomUserAgent(), $options->getCustomCookie() );

		$requestOpts = $options->getExtraOptions();

		$filename = $file->getFileName();
		if ( $options->getCustomFilename() ) {
			$filename = $options->getCustomFilename();
		}

		if ( $options->getApiKey() ) {
			$requestOpts['auth'] = [ '', $options->getApiKey(), 'basic' ];
		}

		$client = new Client( $url, array_merge( [
			'headers' => $headers,
		], $requestOpts ) );

		if ( $file->getStream() ) {
			$temp   = tempnam( sys_get_temp_dir(), 'skynet' );
			$buffer = new LazyOpenStream( $temp, 'wb' );

			Utils::copyToStream( $file->getStream(), $buffer );
			$buffer->close();
		}

		if ( $file->getData() ) {
			$temp = tempnam( sys_get_temp_dir(), 'skynet' );
			file_put_contents( $temp, $file->getData()->toString() );
		}

		if ( isset( $temp ) ) {
			$file->setFileName( $temp );
		}

		$client
			->setKey( generate_uuid4() )
			->file( trailingslashit( $file->getFilePath() ) . $file->getFileName(), $filename )
			->upload();

		if ( isset( $temp ) ) {
			@unlink( $temp );
		}

		return $client->getUrl();
	}

	/**
	 * @param array                                    $files
	 * @param string                                   $filename
	 * @param \Skynet\Options\CustomUploadOptions|null $options
	 *
	 * @return \Skynet\Types\UploadRequestResponse
	 * @throws \Exception
	 */
	public function uploadDirectory( array $files, string $filename = 'untitled', ?CustomUploadOptions $options = null ): UploadRequestResponse {
		$response = $this->uploadDirectoryRequest( $files, $filename, $options );

		validateUploadResponse( $response );

		$response->getBody()->rewind();
		$skylink = formatSkylink( json_decode( $response->getBody()->getContents() )->skylink );

		return new UploadRequestResponse( [ 'skylink' => $skylink ] );
	}

	/*
		private function validateUploadResponse( Response $response ) {
			$response->getBody()->rewind();
			$body = $response->getBody()->getContents();
			$body = json_decode( $body );
			try {
				if ( ! $body ) {
					throw new Exception( 'response->body field missing' );
				}

				validateString( "skylink", $body->data->skylink ?? null, "upload response field" );
			} catch ( Exception $e ) {
				throw new Exception(
					sprintf( 'Did not get a complete upload response despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() )
				);
			}
		}*/

	/**
	 * @param array                                    $files
	 * @param string                                   $filename
	 * @param \Skynet\Options\CustomUploadOptions|null $options
	 *
	 * @return \GuzzleHttp\Psr7\Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function uploadDirectoryRequest( array $files, string $filename, ?CustomUploadOptions $options ) {
		$options = $this->buildUploadOptions( $options );

		/** @var \Skynet\Types\File $file */

		$directories = [];

		foreach ( $files as $file ) {
			if ( ! $file->getData() && is_dir( $file->getFileName() ) ) {

				$iterator = new \RecursiveDirectoryIterator( $file->getFileName(), \FilesystemIterator::SKIP_DOTS );
				foreach ( $iterator as $subFile ) {
					if ( $subFile->isDir() ) {
						continue;
					}
					$subFiles   = [];
					$newFile    = new File( [
						'fileName' => $subFile->getFilename(),
						'filePath' => $subFile->getPath(),
					] );
					$subFiles[] = $newFile;
				}

				$directories[] = $subFiles;
			}
		}

		$basePath = null;

		if ( 1 === count( $directories ) && count( $directories ) === count( $files ) ) {
			$basePath = trailingslashit( $files[0]->getRealPath() );
			$files    = array_pop( $directories );
		}

		foreach ( $files as $file ) {
			$path = $basePath . trailingslashit( str_replace( $basePath, '', $file->getFilePath() ) ) . $file->getFileName();
			if ( $file->getData() ) {
				$formData[] = [
					'name'     => PORTAL_DIRECTORY_FILE_FIELD_NAME,
					'contents' => $file->getData()->toString(),
					'filename' => $path,
				];

			} else {
				$formData[] = [
					'name'     => PORTAL_DIRECTORY_FILE_FIELD_NAME,
					'contents' => $file->getFileName(),
					'filename' => $path,
				];

			}
		}

		$requestOpts['multipart'] = $formData;
		$requestOpts['query']     = [ 'filename' => $filename ];

		if ( $options->getTryFiles() ) {
			$requestOpts['query'] ['tryfiles'] = json_encode( $options->getTryFiles() );
		}
		if ( $options->getErrorPages() ) {
			$requestOpts['query'] ['errorpages'] = json_encode( $options->getErrorPages() );
		}

		return $this->executeRequest( $this->buildRequestOptions( $options->toArray(), [
			'options'      => $requestOpts,
			'endpointPath' => $options->getEndpointUpload(),
			'method'       => 'POST',
		] ) );
	}

	/**
	 * @param string $email
	 * @param string $password
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function setPortalLogin( string $email, string $password ) {
		$this->portalLoginEmail    = $email;
		$this->portalLoginPassword = $password;
		$this->refreshPortalSession();
	}

	/**
	 * @return void
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function refreshPortalSession(): void {
		if ( $this->verifyPortalSession() ) {
			return;
		}
		$response = $this->getHttpClient()->get( $this->portalAccountUrl . '/.ory/kratos/public/self-service/login/browser', [ 'allow_redirects' => false ] );

		$cookies = new CookieJar(
			false, [
			SetCookie::fromString( $response->getHeaderLine( 'Set-Cookie' )
			),
		] );

		$response = $this->getHttpClient()->get( $response->getHeaderLine( 'Location' ), [
			'cookies' => $cookies,
		] );


		libxml_use_internal_errors( true );
		$doc = new \DOMDocument();
		$doc->loadHTML( $response->getBody()->getContents() );

		$xpath = new \DOMXPath( $doc );
		/** @var \DOMElement $form */
		$form      = $doc->getElementsByTagName( 'form' )->item( 0 );
		$csrf      = $xpath->query( "//input[@name='csrf_token']" )->item( 0 )->getAttribute( 'value' );
		$submitUrl = $form->getAttribute( 'action' );

		$response = $this->getHttpClient()->post( $submitUrl, [
			'form_params' => [
				'identifier' => $this->portalLoginEmail,
				'password'   => $this->portalLoginPassword,
				'csrf_token' => $csrf,
			],
			'cookies'     => $cookies,
		] );
		$html     = $response->getBody()->getContents();

		if ( false !== stripos( $html, 'the provided credentials are invalid' ) ) {
			throw new Exception( 'Invalid portal account login' );
		}

		libxml_use_internal_errors( false );

		$this->sessionKey = $cookies->getCookieByName( 'skynet-jwt' )->getValue();
		if ( ! $this->verifyPortalSession() ) {
			throw new Exception( 'There was a problem authenticating with the portal.' );
		}
	}

	/**
	 * @return bool
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function verifyPortalSession(): bool {
		$response = $this->getHttpClient()->get( $this->getPortalUrl() . '/__internal/do/not/use/authenticated', [
			'cookies' => CookieJar::fromArray( [
				'skynet-jwt' => $this->sessionKey,
			], parse_url( $this->getPortalUrl(), PHP_URL_HOST )
			),
		] );
		$json     = json_decode( $response->getBody()->getContents() );

		return $json->authenticated;
	}

	/**
	 * @param string $sessionKey
	 *
	 * @return void
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function setPortalSession( string $sessionKey ): void {
		$this->sessionKey = $sessionKey;
		if ( ! $this->verifyPortalSession() ) {
			throw new Exception( 'There was a problem authenticating with the portal.' );
		}
	}

	/**
	 * @return string
	 */
	public function getPortalAccountUrl(): string {
		return $this->portalAccountUrl;
	}

	/**
	 * @param string $portalAccountUrl
	 */
	public function setPortalAccountUrl( string $portalAccountUrl ): void {
		$this->portalAccountUrl = $portalAccountUrl;
	}

	/**
	 * @param \Skynet\Options\CustomClientOptions|null $options
	 *
	 * @return \Skynet\Options\CustomClientOptions
	 */
	private function buildClientOptions( CustomClientOptions $options = null ): CustomClientOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( self::DEFAULT_DOWNLOAD_OPTIONS, CustomClientOptions::class, $options );
	}
}
