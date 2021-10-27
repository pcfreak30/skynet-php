<?php


namespace unit\functions;

use Codeception\Verify\Verify;
use Exception;
use Skynet\Entity;
use Skynet\Uint8Array;
use function expect;
use function randomUnicodeString;
use function Skynet\functions\options\extractOptions;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;
use function Skynet\functions\url\trimUriPrefix;
use const Skynet\URI_HANDSHAKE_PREFIX;

/**
 * @group functions
 */
class stringTest extends \Codeception\Test\Unit {
	public function testStringbytearrayConversions_ShouldConvertToAndFromValidUtf8StringsWithoutAnyLossOfData() {
		$str = randomUnicodeString( 64 );

		$array  = stringToUint8ArrayUtf8( $str );
		$str2   = $array->toString();
		$array2 = stringToUint8ArrayUtf8( $str2 );

		expect( $str )->toEqual( $str2 );
		expect( $array )->toEqual( $array2 );
	}

	public function testHexToUint8Array_TheHexStringXShouldBeDecodedToX() {
		$hexStrings = [
			[ 'ff', [ 255 ] ],
			[ '0a', [ 10 ] ],
			[ 'ff0a', [ 255, 10 ] ],
		];

		foreach ( $hexStrings as $item ) {
			$byteArray = hexToUint8Array( $item[0] );
			expect( $byteArray->compare( Uint8Array::from( $item[1] ) ) )->toBeTrue();
		}
	}

	public function testHexToUint8Array_ShouldThrowOnInvalidInputX() {
		$invalidHexStrings = [ 'xyz', 'aabbzz', '' ];
		foreach ( $invalidHexStrings as $str ) {
			Verify::Callable( function () use ( $str ) {
				hexToUint8Array( $str );
			} )->throws( Exception::class, sprintf( "Expected 'parameter', 'str', to be a hex-encoded string, was type 'string', value %s", $str ) );
		}
	}

	public function testTrimUriPrefix() {
		$hnsLink                = 'doesn';
		$validHnsLinkVariations = [
			$hnsLink,
			"hns:{$hnsLink}",
			"hns://{$hnsLink}",
			"HNS:{$hnsLink}",
			"HNS://{$hnsLink}",
		];

		foreach ( $validHnsLinkVariations as $variation ) {
			expect( trimUriPrefix( $hnsLink, URI_HANDSHAKE_PREFIX ) )->toEqual( $hnsLink );
		}
	}
}
