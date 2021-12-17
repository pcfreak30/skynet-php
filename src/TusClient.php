<?php

namespace Skynet;

use Carbon\Carbon;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;
use TusPhp\Exception\ConnectionException;
use TusPhp\Exception\FileException;
use TusPhp\Exception\TusException;
use TusPhp\Tus\Client;

class TusClient extends Client {
	public function uploadAsync( int $bytes = - 1 ): PromiseInterface {
		$bytes   = $bytes < 0 ? $this->getFileSize() : $bytes;
		$offset  = $this->partialOffset < 0 ? 0 : $this->partialOffset;
		$promise = new FulfilledPromise( null );
		try {
			// Check if this upload exists with HEAD request.
			$offset = $this->sendHeadRequestAsync();
		} catch ( FileException|ClientException $e ) {
			// Create a new upload.
			$promise = $this->createAsync( $this->getKey() )->then( function ( string $location ) {
				$this->url = $location;
			} );
		} catch ( ConnectException $e ) {
			throw new ConnectionException( "Couldn't connect to server." );
		}

		// Verify that upload is not yet expired.
		if ( $this->isExpired() ) {
			throw new TusException( 'Upload expired.' );
		}

		// Now, resume upload with PATCH request.
		return $promise->then( fn() => $this->sendPatchRequestAsync( $bytes, $offset ) );
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
		} )->reject( function ( ClientException $e ) {
			$statusCode = $e->getResponse()->getStatusCode();

			if ( HttpResponse::HTTP_CREATED !== $statusCode ) {
				throw new FileException( 'Unable to create resource.' );
			}
		} );
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

		try {
			$promise = $this->getClient()->patchAsync( $this->getUrl(), [
				'body'    => $data,
				'headers' => $headers,
			] );

			return $promise->then( function ( Response $response ) {
				return (int) current( $response->getHeader( 'upload-offset' ) );
			} )->reject( function ( $e ) {
				throw $this->handleClientException( $e );
			} );
		} catch ( ClientException $e ) {
			throw $this->handleClientException( $e );
		} catch ( ConnectException $e ) {
			throw new ConnectionException( "Couldn't connect to server." );
		}
	}

}
