<?php


use _support\BaseTest;
use BN\BN;
use Codeception\Verify\Verify;
use Skynet\Registry;
use Skynet\Uint8Array;
use function Skynet\functions\encoding\encodeBigintAsUint64;
use function Skynet\functions\encoding\encodeNumber;
use function Skynet\functions\encoding\encodeUtf8String;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\skylinks\parseSkylink;
/**
 * @group functions
 */
class encodingTest extends BaseTest {
	private array $bigints = [];

	public function testEqualUint8Array_ShouldCorrectlyCheckWhetherUint8arraysAreEqual() {
		expect( Uint8Array::from( [ 0 ] )->compare( Uint8Array::from( [ 0 ] ) ) )->toBeTrue();
		expect( Uint8Array::from( [ 1, 1, 0 ] )->compare( Uint8Array::from( [ 1, 1, 0 ] ) ) )->toBeTrue();
		expect( Uint8Array::from( [ 1, 0, 0 ] )->compare( Uint8Array::from( [ 1, 1, 0 ] ) ) )->toBeFalse();
		expect( Uint8Array::from( [ 1, 1, 0 ] )->compare( Uint8Array::from( [ 1, 1, 0, 0 ] ) ) )->toBeFalse();
	}

	public function testEncodeBigint_ShouldCorrectlyEncodeBigintXAsX() {
		foreach ( $this->bigints as $item ) {
			expect( encodeBigintAsUint64( $item[0] )->compare( Uint8Array::from( $item[1] ) ) )->toBeTrue();
		}
	}

	public function testEncodeBigint_ShouldThrowIfTheBigintIsBeyondTheMaxRevisionAllowed() {
		Verify::Callable( function () {
			encodeBigintAsUint64( Registry::getMaxRevision()->add( new BN( 1 ) ) );
		} )->throws( Exception::class, 'Argument 18446744073709551616does not fit in a 64-bit unsigned integer; exceeds 2^64-1' );

	}

	public function testEncodeNumber_ShouldCorrectlyEncodeNumberXAsX() {
		$numbers = [
			[ 0, [ 0, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ 1, [ 1, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ 255, [ 255, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ 256, [ 0, 1, 0, 0, 0, 0, 0, 0 ] ],
		];

		foreach ( $numbers as $item ) {
			expect( encodeNumber( $item[0] )->compare( Uint8Array::from( $item[1] ) ) )->toBeTrue();
		}
	}

	public function testEncodeUtf8String_ShouldCorrectlyEncodeStringXAsX() {
		$strings = [
			[ "", [ 0, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ "skynet", [ 6, 0, 0, 0, 0, 0, 0, 0, 115, 107, 121, 110, 101, 116 ] ],
		];

		foreach ( $strings as $item ) {
			expect( encodeUtf8String( $item[0] )->compare( Uint8Array::from( $item[1] ) ) )->toBeTrue();
		}
	}

	protected function _before() {
		$this->bigints = [
			[ new BN( 0 ), [ 0, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ new BN( 255 ), [ 255, 0, 0, 0, 0, 0, 0, 0 ] ],
			[ new BN( 256 ), [ 0, 1, 0, 0, 0, 0, 0, 0 ] ],
			[ Registry::getMaxRevision(), [ 255, 255, 255, 255, 255, 255, 255, 255 ] ],
		];
	}

}
