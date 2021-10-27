<?php

namespace Skynet\functions\formatting;

use Exception;
use Skynet\Options\ParseSkylinkEntity;
use Skynet\Uint8Array;
use function Skynet\functions\encoding\encodeSkylinkBase64;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\strings\startsWith;
use function Skynet\functions\strings\trimPrefix;
use function Skynet\functions\strings\trimSuffix;
use function Skynet\functions\validation\validateStringLen;
use const Skynet\BASE32_ENCODED_SKYLINK_SIZE;
use const Skynet\BASE64_ENCODED_SKYLINK_SIZE;
use const Skynet\DEFAULT_PARSE_SKYLINK_OPTIONS;
use const Skynet\SKYLINK_DIRECT_MATCH_POSITION;
use const Skynet\SKYLINK_SUBDOMAIN_REGEX;
use const Skynet\URI_SKYNET_PREFIX;

/**
 *
 */
const BASE32_BITS_5_RIGHT = 31;
/**
 *
 */
const  BASE32_CHARS = '0123456789abcdefghijklmnopqrstuv';


/**
 * @param string                                  $skylinkUrl
 * @param \Skynet\Options\ParseSkylinkEntity|null $options
 *
 * @return string|null
 */
function parseSkylinkBase32( string $skylinkUrl, ParseSkylinkEntity $options = null ): ?string {
	/** @noinspection CallableParameterUseCaseInTypeContextInspection */
	$options = mergeOptions( DEFAULT_PARSE_SKYLINK_OPTIONS, $options );
	/** @noinspection CallableParameterUseCaseInTypeContextInspection */
	$options = makeParseSkylinkOptions( $options );

	$urlParts = parse_url( $skylinkUrl );

	if ( preg_match( '/' . SKYLINK_SUBDOMAIN_REGEX . '/', $urlParts['host'], $matches ) ) {
		if ( $options->isOnlyPath() ) {
			return trimSuffix( $urlParts['path'] ?? '', '/' );
		}

		return $matches[ SKYLINK_DIRECT_MATCH_POSITION ];
	}

	return null;
}

/**
 * @param string $skylink
 *
 * @return string
 * @throws \Exception
 */
function convertSkylinkToBase32( string $skylink ): string {
	$skylink = trimPrefix( $skylink, URI_SKYNET_PREFIX );

	if ( BASE64_ENCODED_SKYLINK_SIZE !== strlen( $skylink ) ) {
		throw new Exception( sprintf( 'Skylink input length is an invalid size of %d, 64 characters expected.', strlen( $skylink ) ) );
	}

	$bytes = decodeSkylinkBase64( $skylink );

	return encodeSkylinkBase32( $bytes );
}

/**
 * @param $bytes
 *
 * @return string
 */
function encodeSkylinkBase32( $bytes ) {
	return strtolower( base32_encode( $bytes, false ) );
}

/**
 * @param string $skylink
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function decodeSkylinkBase64( string $skylink ): Uint8Array {
	$skylink = "{$skylink}==";
	$skylink = str_replace( [ '-', '_' ], [ '+', '/' ], $skylink );

	return Uint8Array::from( $skylink );
}

/**
 * @param string $data
 * @param false  $padRight
 *
 * @return string
 */
function base32_encode( string $data, $padRight = false ) {
	$dataSize      = strlen( $data );
	$res           = '';
	$remainder     = 0;
	$remainderSize = 0;

	for ( $i = 0; $i < $dataSize; $i ++ ) {
		$b             = ord( $data[ $i ] );
		$remainder     = ( $remainder << 8 ) | $b;
		$remainderSize += 8;
		while ( $remainderSize > 4 ) {
			$remainderSize -= 5;
			$c             = $remainder & ( BASE32_BITS_5_RIGHT << $remainderSize );
			$c             >>= $remainderSize;
			$res           .= BASE32_CHARS[ $c ];
		}
	}
	if ( $remainderSize > 0 ) {
		// remainderSize < 5:
		$remainder <<= ( 5 - $remainderSize );
		$c         = $remainder & BASE32_BITS_5_RIGHT;
		$res       .= BASE32_CHARS[ $c ];
	}
	if ( $padRight ) {
		$padSize = ( 8 - ceil( ( $dataSize % 5 ) * 8 / 5 ) ) % 8;
		$res     .= str_repeat( '=', $padSize );
	}

	return $res;
}

/**
 * @param string $skylink
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function decodeSkylinkBase32( string $skylink ): Uint8Array {
	$skylink = strtoupper( $skylink );

	return Uint8Array::from( base32Decode( $skylink ) );
}

/**
 * @param $data
 *
 * @return string
 * @throws \Exception
 */
function base32Decode( $data ) {
	$data     = rtrim( $data, "=\x20\t\n\r\0\x0B" );
	$dataSize = strlen( $data );
	$buf      = 0;
	$bufSize  = 0;
	$res      = '';
	$charMap  = array_flip( str_split( BASE32_CHARS ) ); // char=>value map
	/** @noinspection AdditionOperationOnArraysInspection */
	$charMap += array_flip( str_split( strtoupper( BASE32_CHARS ) ) ); // add upper-case alternatives

	for ( $i = 0; $i < $dataSize; $i ++ ) {
		$c = $data[ $i ];
		if ( ! isset( $charMap[ $c ] ) ) {
			if ( $c === " " || $c === "\r" || $c === "\n" || $c === "\t" ) {
				continue;
			} // ignore these safe characters
			throw new Exception( sprintf( "Encoded string contains unexpected char #%s at offset $i (using improper alphabet?)", ord( $c ) ) );
		}
		$b       = $charMap[ $c ];
		$buf     = ( $buf << 5 ) | $b;
		$bufSize += 5;
		if ( $bufSize > 7 ) {
			$bufSize -= 8;
			$b       = ( $buf & ( 0xff << $bufSize ) ) >> $bufSize;
			$res     .= chr( $b );
		}
	}

	return $res;
}

/**
 * @param $msg
 *
 * @return string
 * @throws \SodiumException
 */
function toHexString( $msg ) {
	if ( is_string( $msg ) ) {
		return \Sodium\bin2hex( $msg );
	}

	if ( $msg instanceof Uint8Array ) {
		return $msg->toHex();
	}

	if ( ! is_array( $msg ) ) {
		throw new Exception( "Not implemented" );
	}

	$binary = pack( ...array_merge( [ 'C*' ], $msg ) );

	return bin2hex( $binary );
}

/**
 * @param       $msg
 * @param false $enc
 *
 * @return array|false|mixed|string
 */
function toBin( $msg, $enc = false ) {
	if ( is_array( $msg ) ) {
		return (array) pack( ...array_merge( [ "C*" ], $msg ) );
	}

	if ( $enc === "hex" ) {
		return hex2bin( $msg );
	}

	return $msg;
}

/**
 * @param $arr
 * @param $enc
 *
 * @return mixed|string
 * @throws \SodiumException
 */
function encode( $arr, $enc ) {
	if ( 'hex' === $enc ) {
		return toHexString( $arr );
	}

	return $arr;
}

/**
 * @param string $skylink
 *
 * @return string
 */
function formatSkylink( string $skylink ): string {
	if ( '' === $skylink ) {
		return $skylink;
	}

	if ( ! startsWith( $skylink, URI_SKYNET_PREFIX ) ) {
		$skylink = URI_SKYNET_PREFIX . $skylink;
	}

	return $skylink;
}

/**
 * @param string $skylink
 *
 * @return string
 * @throws \Exception
 */
function convertSkylinkToBase64( string $skylink ): string {
	$skylink = trimPrefix( $skylink, URI_SKYNET_PREFIX );
	validateStringLen( "skylink", $skylink, "parameter", BASE32_ENCODED_SKYLINK_SIZE );

	$bytes = decodeSkylinkBase32( $skylink );

	return encodeSkylinkBase64( $bytes );
}
