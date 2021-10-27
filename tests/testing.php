<?php

use function Skynet\functions\strings\trimForwardSlash;

function combineStrings( array ...$arrays ): array {
	return array_unique( array_reduce( $arrays, function ( $acc, $array ) {
		if ( empty( $acc ) ) {
			return $array;
		}
		$items = [];
		foreach ( $acc as $first ) {
			foreach ( $array as $second ) {
				$items[] = [ $first . $second ];
			}
		}

		return array_reduce( $items, function ( $acc, $cases ) {
			if ( empty( $acc ) ) {
				return $cases;
			}

			return [ ...$acc, ...$cases ];
		} );
	} ) );
}

function extractNonSkylinkPath( string $url, string $skylink ): string {
	$urlParts = parse_url( $url );
	if ( ! isset( $urlParts['path'] ) ) {
		$urlParts['path'] = '/';
	}

	$path = str_replace( $skylink, '', $urlParts['path'] );
	$path = trimForwardSlash( $path );

	if ( '' !== $path ) {
		$path = "/${path}";
	}

	return $path;
}

function randomUnicodeString( int $length ): string {
	$r = "";

	for ( $i = 0; $i < $length; $i ++ ) {
		$codePoint = random_int( 0x80, 0xffff );
		$char      = \IntlChar::chr( $codePoint );
		if ( $char !== null && \IntlChar::isprint( $char ) ) {
			$r .= $char;
		} else {
			$i --;
		}
	}

	return $r;

	return implode( '', $output );
}
