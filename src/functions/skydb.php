<?php

namespace Skynet\functions\skydb;

use Skynet\Uint8Array;
use function Skynet\functions\encoding\encodeSkylinkBase64;
use function Skynet\functions\formatting\formatSkylink;
use function Skynet\functions\validation\throwValidationError;
use function Skynet\functions\validation\validateSkylinkString;
use const Skynet\BASE64_ENCODED_SKYLINK_SIZE;
use const Skynet\RAW_SKYLINK_SIZE;

/**
 * @param string      $rawDataLink
 * @param string|null $cachedDataLink
 *
 * @return bool
 */
function checkCachedDataLink( string $rawDataLink, ?string $cachedDataLink ): bool {
	if ( $cachedDataLink ) {
		$cachedDataLink = validateSkylinkString( 'cachedDataLink', $cachedDataLink, 'optional parameter' );

		return $rawDataLink === $cachedDataLink;
	}

	return false;
}

/**
 * @param \Skynet\Uint8Array $data
 * @param bool               $legacy
 *
 * @return array
 * @throws \Exception
 */
function parseDataLink( Uint8Array $data, bool $legacy ) {
	$rawDataLink = '';
	if ( $legacy && $data->getMaxLength() === BASE64_ENCODED_SKYLINK_SIZE ) {
		$rawDataLink = $data->toString();
	} elseif ( $data->getMaxLength() === RAW_SKYLINK_SIZE ) {
		$rawDataLink = encodeSkylinkBase64( $data );
	} else {
		throwValidationError( 'entry->data', $data, "returned entry data", sprintf( 'length %d bytes', RAW_SKYLINK_SIZE ) );
	}

	return [ 'rawDataLink' => $rawDataLink, 'dataLink' => formatSkylink( $rawDataLink ) ];
}
