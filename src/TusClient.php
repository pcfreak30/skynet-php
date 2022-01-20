<?php

namespace Skynet;

use Carbon\Carbon;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\StreamInterface;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use TusPhp\Exception\ConnectionException;
use TusPhp\Exception\FileException;
use TusPhp\Exception\TusException;
use TusPhp\Tus\Client;

class TusClient extends Client {
	private StreamInterface $stream;

	public function __construct( GuzzleClient $client, string $apiEndpoint, array $options = [] ) {
		parent::__construct( '', $options );
		$this->headers += [ 'Tus-Resumable' => self::TUS_PROTOCOL_VERSION ];
		$this->client  = $client;
		$this->setApiPath( $apiEndpoint );
	}

	public function uploadAsync( int $bytes = - 1, $offset = 0, bool $init = false ): PromiseInterface {
		$bytes   = $bytes < 0 ? $this->getFileSize() : $bytes;
		$promise = Create::promiseFor( null );

		if ( $init ) {
			$promise = $promise->then( fn() => $this->sendHeadRequestAsync() );
		}

		return $promise->otherwise( function ( $e ) {
			if ( $e instanceof FileException || $e instanceof ClientException ) {
				// Create a new upload.
				return $this->createAsync( $this->getKey() )->then( function ( string $location ) {
					$this->url = $location;
				} );
			}
			if ( $e instanceof ConnectException ) {
				throw new ConnectionException( "Couldn't connect to server." );
			}

			if ( $e instanceof \Exception ) {
				throw $e;
			}

			return Create::promiseFor( null );
		} )->then( function ( ?int $newOffset ) use ( &$offset, &$bytes ) {
			if ( $this->isExpired() ) {
				throw new TusException( 'Upload expired.' );
			}

			return $this->sendPatchRequestAsync( $bytes, $newOffset ?? $offset );
		} );
	}

	protected function sendHeadRequestAsync(): PromiseInterface {
		$promise = $this->getClient()->headAsync( $this->getUrl() );

		return $promise->then( function ( Response $response ) {
			$statusCode = $response->getStatusCode();

			if ( HttpResponse::HTTP_OK !== $statusCode ) {
				throw new FileException( 'File not found.' );
			}

			return (int) current( $response->getHeader( 'upload-offset' ) );
		} );
	}

	/**
	 * Create resource with POST request.
	 *
	 * @param string $key
	 *
	 * @return string
	 * @throws GuzzleException
	 *
	 * @throws FileException
	 */
	public function createAsync( string $key ): PromiseInterface {
		return $this->createWithUploadAsync( $key, 0 )->then( function ( array $result ) {
			return $result['location'];
		} );
	}

	/**
	 * Create resource with POST request and upload data using the creation-with-upload extension.
	 *
	 * @see https://tus.io/protocols/resumable-upload.html#creation-with-upload
	 *
	 * @param string $key
	 * @param int    $bytes -1 => all data; 0 => no data
	 *
	 * @throws GuzzleException
	 *
	 * @return array [
	 *     'location' => string,
	 *     'offset' => int
	 * ]
	 */
	public function createWithUploadAsync( string $key, int $bytes = - 1 ): PromiseInterface {
		$bytes = $bytes < 0 ? $this->fileSize : $bytes;

		$headers = $this->headers + [
				'Upload-Length'   => $this->fileSize,
				'Upload-Key'      => $key,
				'Upload-Checksum' => $this->getUploadChecksumHeader(),
				'Upload-Metadata' => $this->getUploadMetadataHeader(),
			];

		$data = '';
		if ( $bytes > 0 ) {
			$data = $this->getData( 0, $bytes );

			$headers += [
				'Content-Type'   => self::HEADER_CONTENT_TYPE,
				'Content-Length' => \strlen( $data ),
			];
		}

		if ( $this->isPartial() ) {
			$headers += [ 'Upload-Concat' => 'partial' ];
		}

		try {
			$promise = $this->getClient()->postAsync( $this->apiPath, [
				'body'    => $data,
				'headers' => $headers,
			] );
		} catch ( ClientException $e ) {
			$statusCode = $e->getResponse()->getStatusCode();

			if ( HttpResponse::HTTP_CREATED !== $statusCode ) {
				throw new FileException( 'Unable to create resource.' );
			}
		}

		return $promise->then( function ( Response $response ) use ( $bytes ) {
			$uploadOffset   = $bytes > 0 ? current( $response->getHeader( 'upload-offset' ) ) : 0;
			$uploadLocation = current( $response->getHeader( 'location' ) );

			$this->getCache()->set( $this->getKey(), [
				'location'   => $uploadLocation,
				'expires_at' => Carbon::now()->addSeconds( $this->getCache()->getTtl() )->format( $this->getCache()::RFC_7231 ),
			] );

			return [
				'location' => $uploadLocation,
				'offset'   => $uploadOffset,
			];
		} )->otherwise( function ( ClientException $e ) {
			$statusCode = $e->getResponse()->getStatusCode();

			if ( HttpResponse::HTTP_CREATED !== $statusCode ) {
				throw new FileException( 'Unable to create resource.' );
			}
		} );
	}

	protected function getData( int $offset, int $bytes ): string {
		$stream = $this->stream;
		$stream->seek( $offset );

		return $stream->read( $bytes );
	}

	protected function sendPatchRequestAsync( int $bytes, int $offset ): PromiseInterface {
		$data    = $this->getData( $offset, $bytes );
		$headers = $this->headers + [
				'Content-Type'    => self::HEADER_CONTENT_TYPE,
				'Content-Length'  => \strlen( $data ),
				'Upload-Checksum' => $this->getUploadChecksumHeader(),
			];

		if ( $this->isPartial() ) {
			$headers += [ 'Upload-Concat' => self::UPLOAD_TYPE_PARTIAL ];
		} else {
			$headers += [ 'Upload-Offset' => $offset ];
		}

		return $this->getClient()->patchAsync( $this->getUrl(), [
			'body'    => $data,
			'headers' => $headers,
		] )->then( function ( Response $response ) {
			return (int) current( $response->getHeader( 'upload-offset' ) );
		} )->otherwise( function ( $e ) {
			throw $this->handleException( $e );
		} );

	}

	protected function handleException( BadResponseException $e ) {
		$response   = $e->getResponse();
		$statusCode = $response !== null ? $response->getStatusCode() : HttpResponse::HTTP_INTERNAL_SERVER_ERROR;

		if ( HttpResponse::HTTP_REQUESTED_RANGE_NOT_SATISFIABLE === $statusCode ) {
			return new FileException( 'The uploaded file is corrupt.' );
		}

		if ( HttpResponse::HTTP_CONTINUE === $statusCode ) {
			return new ConnectionException( 'Connection aborted by user.' );
		}

		if ( HttpResponse::HTTP_UNSUPPORTED_MEDIA_TYPE === $statusCode ) {
			return new TusException( 'Unsupported media types.' );
		}

		return new TusException( $response->getBody(), $statusCode );
	}

	/**
	 * Set file properties.
	 *
	 * @param string $file File path.
	 * @param string $name File name.
	 *
	 * @return Client
	 */
	public function stream( StreamInterface $stream, string $name ): self {
		$this->stream = $stream;

		$this->fileName = $name;
		$this->fileSize = $stream->getSize();

		$this->addMetadata( 'filename', $this->fileName );

		return $this;
	}

	/**
	 * Get checksum.
	 *
	 * @return string
	 */
	public function getChecksum(): string {
		if ( empty( $this->checksum ) ) {
			$this->setChecksum( \GuzzleHttp\Psr7\Utils::hash( $this->getStream(), $this->getChecksumAlgorithm() ) );
		}

		return $this->checksum;
	}

	/**
	 * @return \Psr\Http\Message\StreamInterface
	 */
	public function getStream(): StreamInterface {
		if ( ! ( $this->stream ?? null ) ) {
			throw new \Exception( 'stream has not been set.' );
		}

		return $this->stream;
	}
}
