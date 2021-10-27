<?php

namespace Skynet\functions\upload;

use Exception;
use GuzzleHttp\Psr7\Response;
use function Skynet\functions\validation\validateString;

/**
 * @param \GuzzleHttp\Psr7\Response $response
 *
 * @return void
 * @throws \Exception
 */
function validateUploadResponse( Response $response ) {
	try {
		$response->getBody()->rewind();
		$body = $response->getBody()->getContents();
		if ( ! $body ) {
			throw new Exception( 'response->body field missing' );
		}
		$body = json_decode( $body );
		validateString( "skylink", $body->skylink ?? null, "upload response field" );
	} catch ( Exception $e ) {
		throw new Exception(
			sprintf( 'Did not get a complete upload response despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() )
		);
	}
}

/**
 * @param \GuzzleHttp\Psr7\Response $response
 *
 * @return void
 * @throws \Exception
 */
function validateLargeUploadResponse( Response $response ) {
	try {
		if ( ! $response->getHeaders() || empty( $response->getHeaders() ) ) {
			throw new Exception( 'response->headers field missing' );
		}
		validateString( 'response->headers["skynet-skylink"]', $response->getHeader( 'skynet-skylink' ), "upload response field" );
	} catch ( Exception $e ) {
		throw new Exception(
			sprintf( 'Did not get a complete upload response despite a successful request. Please try again and report this issue to the devs if it persists. %s', $e->getMessage() )
		);
	}
}

/**
 * @return string
 * @throws \Exception
 */
function generate_uuid4() {
	return sprintf(
		'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0x0fff ) | 0x4000,
		random_int( 0, 0x3fff ) | 0x8000,
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff ),
		random_int( 0, 0xffff )
	);
}
