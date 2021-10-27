<?php

namespace Skynet\functions\encoding;

use BN\BN;
use Skynet\Uint8Array;
use function Skynet\functions\validation\assertUint64;

/**
 * @param \Skynet\Uint8Array $bytes
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function encodePrefixedBytes( Uint8Array $bytes ): Uint8Array {
	$length   = count( $bytes );
	$intArray = new Uint8Array( 8 + $length );
	$intArray->set( $length );
	$intArray->set( $bytes, 8 );

	return $intArray;
}

/**
 * @param \Skynet\Uint8Array $bytes
 *
 * @return string
 */
function encodeSkylinkBase64( Uint8Array $bytes ): string {
	$base64 = base64_encode( $bytes->toString() );
	$base64 = str_replace( [ '+', '/' ], [ '-', '_' ], $base64 );

	return substr( $base64, 0, - 2 );
}

/**
 * @param string $str
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function encodeUtf8String( string $str ): Uint8Array {
	$bytes   = Uint8Array::from( $str );
	$encoded = new Uint8Array( 8 + $bytes->count() );
	$encoded->set( encodeNumber( $bytes->count() ) );
	$encoded->set( $bytes, 8 );

	return $encoded;
}

/**
 * @param int $num
 *
 * @return \Skynet\Uint8Array
 */
function encodeNumber( int $num ): Uint8Array {
	$encoded = new Uint8Array( 8 );

	for ( $i = 0; $i < $encoded->count(); $i ++ ) {
		$byte          = $num & 0xff;
		$encoded[ $i ] = $byte;
		$num           >>= 8;
	}

	return $encoded;
}

/**
 * @param \BN\BN $int
 *
 * @return \Skynet\Uint8Array
 * @throws \Exception
 */
function encodeBigintAsUint64( BN $int ) {
	assertUint64( $int );

	$encoded = new Uint8Array( 8 );

	for ( $i = 0; $i < $encoded->count(); $i ++ ) {
		$encoded[ $i ] = $int->andln( 0xff );
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		$int = $int->shrn( 8 );
	}

	return $encoded;
}
