<?php

namespace Skynet\functions\skylinks;

use Exception;
use Skynet\Options\CustomDownloadOptions;
use Skynet\Options\ParseSkylinkEntity;
use Skynet\Skynet;
use function Skynet\functions\formatting\convertSkylinkToBase32;
use function Skynet\functions\formatting\parseSkylinkBase32;
use function Skynet\functions\options\makeDownloadOptions;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\strings\trimForwardSlash;
use function Skynet\functions\strings\trimSuffix;
use function Skynet\functions\url\add_query_arg;
use function Skynet\functions\url\addSubdomain;
use function Skynet\functions\url\encodeURIComponent;
use function Skynet\functions\url\makeUrl;
use function Skynet\functions\url\trimUriPrefix;
use function Skynet\functions\validation\validateString;
use const Skynet\DEFAULT_PARSE_SKYLINK_OPTIONS;
use const Skynet\SKYLINK_DIRECT_MATCH_POSITION;
use const Skynet\SKYLINK_DIRECT_REGEX;
use const Skynet\SKYLINK_PATH_MATCH_POSITION;
use const Skynet\SKYLINK_PATHNAME_REGEX;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @param string                                     $portalUrl
 * @param string                                     $skylinkUrl
 * @param \Skynet\Options\CustomDownloadOptions|null $options
 *
 * @return string
 * @throws \Exception
 */
function getSkylinkUrlForPortal( string $portalUrl, string $skylinkUrl, CustomDownloadOptions $options = null ) {

	$options = mergeOptions( Skynet::DEFAULT_DOWNLOAD_OPTIONS, $options );
	$options = makeDownloadOptions( $options );

	$path  = explode( '/', $options->getPath() );
	$path  = array_map( function ( $element ) {
		return encodeURIComponent( $element );
	}, $path );
	$path  = implode( '/', $path );
	$query = buildDownloadQuery( $options->getDownload() );

	$optPath = $options->getPath();
	if ( $optPath && is_string( $optPath ) && in_array( $optPath, [ '0', '1' ] ) ) {
		throw new Exception( "\$options->path has to be a string, bool provided" );
	}

	if ( $options->isSubdomain() ) {
		$skylinkPath = parseSkylink( $skylinkUrl, makeParseSkylinkOptions( [ 'onlyPath' => true ] ) ) ?? '';
		$skylink     = parseSkylink( $skylinkUrl );
		if ( null === $skylink ) {
			throw  new Exception( sprintf( 'Could not get skylink out of input %s', $skylink ) );
		}

		$skylink = convertSkylinkToBase32( $skylink );
		$url     = addSubdomain( $portalUrl, $skylink );
		$url     = makeUrl( $url, $skylinkPath, $path );
	} else {
		$skylink = parseSkylink( $skylinkUrl, makeParseSkylinkOptions( [ 'includePath' => true ] ) );
		if ( null === $skylink ) {
			throw  new Exception( sprintf( 'Could not get skylink with path out of input %s', $skylinkUrl ) );
		}

		$url = makeUrl( $portalUrl, $options->getEndpointDownload(), $skylink );
		$url = makeUrl( $url, $path );
	}

	$urlParts = parse_url( $url );
	if ( ! isset( $urlParts['path'] ) || '' === $urlParts['path'] ) {
		$urlParts['path'] = '/';
		$url              = http_build_url( $urlParts );
	}

	return add_query_arg( $query, $url );
}

/**
 * @param string                                  $skylinkUrl
 * @param \Skynet\Options\ParseSkylinkEntity|null $options
 *
 * @return mixed|string|null
 * @throws \Exception
 */
function parseSkylink( string $skylinkUrl, ParseSkylinkEntity $options = null ) {
	validateString( "skylinkUrl", $skylinkUrl, "parameter" );


	$options = mergeOptions( DEFAULT_PARSE_SKYLINK_OPTIONS, $options );
	$options = makeParseSkylinkOptions( $options );

	if ( $options->isIncludePath() && $options->isOnlyPath() ) {
		throw new Exception( 'The includePath and onlyPath options cannot both be set' );
	}
	if ( $options->isIncludePath() && $options->isFromSubdomain() ) {
		throw new Exception( 'The includePath and fromSubdomain options cannot both be set' );
	}
	if ( $options->isFromSubdomain() ) {
		return parseSkylinkBase32( $skylinkUrl, $options );
	}

	$skylinkUrl = trimUriPrefix( $skylinkUrl, URI_SKYNET_PREFIX );

	if ( preg_match( '/' . SKYLINK_DIRECT_REGEX . '/', $skylinkUrl, $matches ) ) {
		if ( $options->isOnlyPath() ) {
			return '';
		}

		return $matches[ SKYLINK_DIRECT_MATCH_POSITION ];
	}

	$urlParts       = parse_url( $skylinkUrl );
	$skylinkAndPath = trimSuffix( $urlParts['path'], '/' );
	if ( ! preg_match( '@' . SKYLINK_PATHNAME_REGEX . '@', $skylinkAndPath, $matches ) ) {
		return null;
	}

	$path = $matches[ SKYLINK_PATH_MATCH_POSITION ];
	if ( $options->isIncludePath() ) {
		return trimForwardSlash( $skylinkAndPath );
	}
	if ( $options->isOnlyPath() ) {
		return $path;
	}

	return $matches[ SKYLINK_DIRECT_MATCH_POSITION ];
}

/**
 * @param bool $download
 *
 * @return array
 */
function buildDownloadQuery( bool $download ): array {
	$query = [];

	if ( $download ) {
		$query['attachment'] = 'true';
	}

	return $query;
}
