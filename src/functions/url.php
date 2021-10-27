<?php

namespace Skynet\functions\url;
/*
 * URL Utility functions copied from WordPress. D.R.Y.
 */

use Exception;
use function Skynet\functions\strings\startsWith;
use function Skynet\functions\strings\trimForwardSlash;
use function Skynet\functions\strings\trimSuffix;
use function Skynet\functions\validation\throwValidationError;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @param ...$args
 *
 * @return string
 */
function add_query_arg( ...$args ): string {
	if ( is_array( $args[0] ) ) {
		if ( count( $args ) < 2 || false === $args[1] ) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[1];
		}
	} else {
		if ( count( $args ) < 3 || false === $args[2] ) {
			$uri = $_SERVER['REQUEST_URI'];
		} else {
			$uri = $args[2];
		}
	}

	$frag = strstr( $uri, '#' );
	if ( $frag ) {
		$uri = substr( $uri, 0, - strlen( $frag ) );
	} else {
		$frag = '';
	}

	if ( 0 === stripos( $uri, 'http://' ) ) {
		$protocol = 'http://';
		$uri      = substr( $uri, 7 );
	} elseif ( 0 === stripos( $uri, 'https://' ) ) {
		$protocol = 'https://';
		$uri      = substr( $uri, 8 );
	} else {
		$protocol = '';
	}

	if ( strpos( $uri, '?' ) !== false ) {
		list( $base, $query ) = explode( '?', $uri, 2 );
		$base .= '?';
	} elseif ( $protocol || strpos( $uri, '=' ) === false ) {
		$base  = $uri . '?';
		$query = '';
	} else {
		$base  = '';
		$query = $uri;
	}

	parse_str( $query, $qs );
	$qs = urlencode_deep( $qs ); // This re-URL-encodes things that were already in the query string.
	if ( is_array( $args[0] ) ) {
		foreach ( $args[0] as $k => $v ) {
			$qs[ $k ] = $v;
		}
	} else {
		$qs[ $args[0] ] = $args[1];
	}

	foreach ( $qs as $k => $v ) {
		if ( false === $v ) {
			unset( $qs[ $k ] );
		}
	}

	$ret = build_query( $qs );
	$ret = trim( $ret, '?' );
	$ret = preg_replace( '#=(&|$)#', '$1', $ret );
	$ret = $protocol . $base . $ret . $frag;
	$ret = rtrim( $ret, '?' );

	return $ret;
}

/**
 * @param $value
 *
 * @return array|false|mixed
 */
function urlencode_deep( $value ) {
	return map_deep( $value, 'urlencode' );
}

/**
 * @param $value
 * @param $callback
 *
 * @return array|false|mixed
 */
function map_deep( $value, $callback ) {
	if ( is_array( $value ) ) {
		foreach ( $value as $index => $item ) {
			$value[ $index ] = map_deep( $item, $callback );
		}
	} elseif ( is_object( $value ) ) {
		$object_vars = get_object_vars( $value );
		foreach ( $object_vars as $property_name => $property_value ) {
			/** @noinspection PhpReadonlyPropertyWrittenOutsideDeclarationScopeInspection */
			$value->$property_name = map_deep( $property_value, $callback );
		}
	} else {
		$value = $callback( $value );
	}

	return $value;
}

/**
 * @param        $data
 * @param null   $prefix
 * @param null   $sep
 * @param string $key
 * @param bool   $urlencode
 *
 * @return string
 */
function _http_build_query( $data, $prefix = null, $sep = null, $key = '', $urlencode = true ) {
	$ret = array();

	foreach ( (array) $data as $k => $v ) {
		if ( $urlencode ) {
			$k = urlencode( $k );
		}
		if ( is_int( $k ) && null != $prefix ) {
			$k = $prefix . $k;
		}
		if ( ! empty( $key ) ) {
			$k = $key . '%5B' . $k . '%5D';
		}
		if ( null === $v ) {
			continue;
		}

		if ( false === $v ) {
			$v = '0';
		}

		if ( is_array( $v ) || is_object( $v ) ) {
			$ret[] = _http_build_query( $v, '', $sep, $k, $urlencode );
		} elseif ( $urlencode ) {
			$ret[] = $k . '=' . urlencode( $v );
		} else {
			$ret[] = $k . '=' . $v;
		}
	}

	if ( null === $sep ) {
		$sep = ini_get( 'arg_separator.output' );
	}

	return implode( $sep, $ret );
}

/**
 * @param $data
 *
 * @return string
 */
function build_query( $data ) {
	return _http_build_query( $data, null, '&', '', false );
}

/**
 * @param string ...$args
 *
 * @return string
 * @throws \Exception
 */
function makeUrl( string ...$args ): string {
	if ( 0 === count( $args ) ) {
		throwValidationError( "args", $args, "parameter", "non-empty" );
	}

	return array_reduce( $args, fn( $acc, $cur ) => urljoin( $acc, $cur ), '' );
}

/**
 * @param ...$args
 *
 * @return string
 * @throws \Exception
 */
function urljoin( ...$args ) {
	$len     = count( $args );
	$results = [];
	if ( 0 === $len ) {
		return '';
	}

	if ( ! is_string( $args[0] ) ) {
		throw new Exception( sprintf( 'Url must be a string. Received %s', $args[0] ) );
	}

	if ( preg_match( '/^[^\/:]+:\/*$/', $args[0] ) && 1 < $len ) {
		$first   = array_shift( $args );
		$args[0] = $first . $args[0];
	}
	if ( preg_match( '/^file:\/\/\//', $args[0] ) ) {
		$args[0] = preg_replace( '/^([^\/:]+):\/*/', '$1:///', $args[0] );
	} else {
		$args[0] = preg_replace( '/^([^\/:]+):\/*/', '$1://', $args[0] );
	}

	$len = count( $args );

	foreach ( $args as $i => $value ) {
		$component = $value;

		if ( ! is_string( $component ) ) {
			throw new Exception( sprintf( 'Url must be a string. Received %s', $component ) );
		}

		if ( '' === $component ) {
			continue;
		}

		if ( $i > 0 ) {
			$component = preg_replace( '/^[\/]+/', '', $component );
		}

		if ( $i < $len - 1 ) {
			$component = preg_replace( '/[\/]+$/', '', $component );
		} else {
			$component = preg_replace( '/[\/]+$/', '/', $component );
		}

		$results[] = $component;
	}

	$str = implode( '/', $results );

	$str = preg_replace( '/\/(\?|&|#[^!])/', '$1', $str );

	$parts = explode( '?', $str );

	$str = array_shift( $parts );
	if ( 0 < count( $parts ) ) {
		$str .= '?';
	}

	$str .= implode( '&', $parts );

	return $str;

}

/**
 * @param $str
 *
 * @return string
 */
function encodeURIComponent( $str ): string {
	$revert = array( '%21' => '!', '%2A' => '*', '%27' => "'", '%28' => '(', '%29' => ')' );

	return strtr( rawurlencode( $str ), $revert );
}

/**
 * @param string $string
 * @param string $prefix
 *
 * @return string
 */
function trimUriPrefix( string $string, string $prefix ): string {
	$longPrefix  = strtolower( $prefix );
	$shortPrefix = trimSuffix( $longPrefix, '/' );
	$strLower    = strtolower( $string );
	if ( startsWith( $strLower, $longPrefix ) ) {
		return substr( $string, strlen( $longPrefix ) );
	}
	if ( startsWith( $strLower, $shortPrefix ) ) {
		return substr( $string, strlen( $shortPrefix ) );
	}

	return $string;
}

/**
 * @param string $url
 * @param string $subdomain
 *
 * @return string
 */
function addSubdomain( string $url, string $subdomain ): string {
	$urlParts         = parse_url( $url );
	$urlParts['host'] = strtolower( "{$subdomain}.{$urlParts['host']}" );
	$string           = http_build_url( $urlParts );

	return trimSuffix( $string, '/' );
}

/**
 * @param $string
 *
 * @return string
 */
function untrailingslashit( $string ) {
	return rtrim( $string, '/\\' );
}

/**
 * @param $string
 *
 * @return string
 */
function trailingslashit( $string ) {
	return untrailingslashit( $string ) . '/';
}

/**
 * @param string $portalUrl
 * @param string $domain
 *
 * @return string
 */
function getFullDomainUrlForPortal( string $portalUrl, string $domain ): string {
	$domain = trimUriPrefix( $domain, URI_SKYNET_PREFIX );
	$domain = trimForwardSlash( $domain );

	$path     = null;
	$urlParts = parse_url( $domain );

	if ( ! isset( $urlParts['host'] ) && ! isset( $urlParts['scheme'] ) && isset( $urlParts['path'] ) ) {
		$domain   = "//" . $domain;
		$urlParts = parse_url( $domain );
	}

	$domain = $urlParts['host'] ?? $domain;
	$path   = $urlParts['path'] ?? $path;

	if ( 'localhost' === $domain ) {
		$url = 'localhost';
	} else {
		$url = addSubdomain( $portalUrl, $domain );
	}

	if ( $path ) {
		$url = addPath( $url, $path );
	}

	return $url;
}

/**
 * @param string $url
 * @param string $path
 *
 * @return string
 */
function addPath( string $url, string $path ): string {
	$path = trimForwardSlash( $path );

	if ( 'localhost' === $url ) {
		$str = "localhost/{$path}";
	} else {

		$urlParts         = parse_url( $url );
		$urlParts['path'] = $path;
		$str              = http_build_url( $urlParts );
	}

	return trimSuffix( $str, '/' );
}

/**
 * @param string $portalUrl
 * @param string $fullDomain
 *
 * @return string
 */
function extractDomainForPortal( string $portalUrl, string $fullDomain ): string {
	$fullDomainObj = parse_url( $fullDomain );

	$path = '';

	if ( ! isset( $fullDomainObj['host'] ) && ! isset( $fullDomainObj['scheme'] ) && isset( $fullDomainObj['path'] ) ) {
		$fullDomain    = trimForwardSlash( $fullDomain );
		$fullDomain    = "//" . $fullDomain;
		$fullDomainObj = parse_url( $fullDomain );
	}

	$fullDomain = $fullDomainObj['host'] ?? $fullDomain;
	$path       = $fullDomainObj['path'] ?? $path;
	$fullDomain = strtolower( $fullDomain );
	$path       = trimForwardSlash( $path );

	$portalUrlObj = parse_url( $portalUrl );
	$portalDomain = trimForwardSlash( $portalUrlObj['host'] );

	$domain = trimSuffix( $fullDomain, $portalDomain, 1 );
	$domain = trimSuffix( $domain, '.' );

	if ( $path && '' !== $path ) {
		$path   = trimForwardSlash( $path );
		$domain = $domain . '/' . $path;
	}

	return $domain;
}
