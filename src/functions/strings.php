<?php

namespace Skynet\functions\strings;

use Skynet\Uint8Array;
use function Skynet\functions\validation\throwValidationError;
use function Skynet\functions\validation\validateHexString;

/**
 * @param $string
 * @param $from
 * @param $to
 *
 * @return false|string
 */
function substring( $string, $from, $to ) {
	return substr( $string, $from, $to - $from );
}

/**
 * @param string $string
 * @param string $suffix
 * @param null   $limit
 *
 * @return false|string
 */
function trimSuffix( string $string, string $suffix, $limit = null ) {
	while ( endsWith( $string, $suffix ) ) {
		if ( null !== $limit && $limit <= 0 ) {
			break;
		}

		$string = substring( $string, 0, strlen( $string ) - strlen( $suffix ) );
		if ( $limit ) {
			$limit -= 1;
		}
	}

	return $string;
}

/**
 * @param string $haystack
 * @param string $needle
 *
 * @return bool
 */
function endsWith( string $haystack, string $needle ): bool {
	$length = strlen( $needle );
	if ( ! $length ) {
		return true;
	}

	return substr( $haystack, - $length ) === $needle;
}

/**
 * @param string $string
 *
 * @return string
 */
function trimForwardSlash( string $string ): string {
	return trimPrefix( trimSuffix( $string, '/' ), '/' );
}

/**
 * @param string $string
 * @param string $prefix
 * @param null   $limit
 *
 * @return false|string
 */
function trimPrefix( string $string, string $prefix, $limit = null ) {
	while ( startsWith( $string, $prefix ) ) {
		if ( null !== $limit && $limit <= 0 ) {
			break;
		}

		$string = substr( $string, strlen( $prefix ) );
		if ( $limit ) {
			$limit -= 1;
		}
	}

	return $string;
}

/**
 * @param $haystack
 * @param $needle
 *
 * @return bool
 */
function startsWith( string $haystack, string $needle ): bool {
	$length = strlen( $needle );

	return substr( $haystack, 0, $length ) === $needle;
}

/**
 * @param string $str
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function stringToUint8ArrayUtf8( string $str ): Uint8Array {
	return Uint8Array::from( str_split( $str ) );
}

/**
 * @param string $str
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function hexToUint8Array( string $str ): Uint8Array {
	return Uint8Array::from( hexToArray( $str ) );
}

/**
 * @param string $str
 *
 * @return array
 * @throws \Exception
 */
function hexToArray( string $str ): array {
	validateHexString( "str", $str, "parameter" );

	if ( empty( $str ) ) {
		throwValidationError( 'str', $str, 'parameter', 'a hex-encoded string' );
	}

	$str = str_split( $str, 2 );

	return array_map( 'hex2bin', $str );
}

/**
 * @param string $str
 *
 * @return string
 */
function hexToString( string $str ): string {
	return hex2bin( $str );
}


/**
 * @param string $str
 *
 * @return bool
 */
function isHexString( string $str ) {
	$ret = preg_match( '/^[0-9A-Fa-f]*$/', $str );

	return false !== $ret && 0 !== $ret;
}

/**
 * @param string $str
 * @param string $prefix
 *
 * @return string
 */
function ensurePrefix( string $str, string $prefix ) {
	if ( ! startsWith( $str, $prefix ) ) {
		$str = $prefix . $str;
	}

	return $str;
}
