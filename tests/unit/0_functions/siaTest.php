<?php


use _support\BaseTest;
use Codeception\Verify\Verify;
use Skynet\SiaSkylink;
use Skynet\Uint8Array;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\sia\decodeSkylink;
use function Skynet\functions\sia\isSkylinkV1;
use function Skynet\functions\sia\isSkylinkV2;
use function Skynet\functions\sia\newEd25519PublicKey;
use function Skynet\functions\sia\newSkylinkV2;
use function Skynet\functions\sia\newSpecifier;
use function Skynet\functions\skylinks\parseSkylink;
use function Skynet\functions\strings\hexToUint8Array;
use const Skynet\ERR_SKYLINK_INCORRECT_SIZE;
/**
 * @group functions
 */
class siaTest extends BaseTest {
	private string $skylinkV1 = 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';
	private string $skylinkV2 = 'AQA7pRL8JEXcIuDbjsVyucWvprL4aD6feNDWwylo19vS2w';
	private Uint8Array $expectedBytes;
	private array $skylinks = [];

	public function testDecodeSkylink_ShouldDecodeABase64Skylink() {
		$bytes = decodeSkylink( $this->skylinkV1 );

		expect( $bytes->compare( $this->expectedBytes ) )->toBeTrue();
	}

	public function testDecodeSkylink_ShouldDecodeABase32Skylink() {
		$bytes = decodeSkylink( 'bg06v2tidkir84hg0s1s4t97jaeoaa1jse1svrad657u070c9calq4g' );

		expect( $bytes->compare( $this->expectedBytes ) )->toBeTrue();
	}

	public function testDecodeSkylink_ShouldFailOnInvalidInputStringLength() {
		Verify::Callable( function () {
			decodeSkylink( '' );
		} )->throws( Exception::class, ERR_SKYLINK_INCORRECT_SIZE );
	}

	public function testIsSkylinkV1_ShouldWorkForV1AndV2Skylinks() {
		expect( isSkylinkV1( $this->skylinkV1 ) )->toBeTrue();
		expect( isSkylinkV1( $this->skylinkV2 ) )->toBeFalse();
	}

	public function testIsSkylinkV2_ShouldWorkForV1AndV2Skylinks() {
		expect( isSkylinkV2( $this->skylinkV1 ) )->toBeFalse();
		expect( isSkylinkV2( $this->skylinkV2 ) )->toBeTrue();
	}

	public function testnewSpecifier_ShouldReturnCorrectSpecifierForGivenString() {
		$specifier = 'testing';
		$expected  = Uint8Array::from( [ 116, 101, 115, 116, 105, 110, 103, 0, 0, 0, 0, 0, 0, 0, 0, 0 ] );
		expect( newSpecifier( $specifier )->compare( $expected ) )->toBeTrue();
	}

	public function testNewSkylinkV2_ShouldCreateV2SkylinksCorrectly() {
		$publicKey       = 'a1790331b8b41a94644d01a7b482564e7049047812364bcabc32d399ad23f7e2';
		$dataKey         = 'd321b3c31337047493c9b5a99675e9bdaea44218a31aad2fd7738209e7a5aca1';
		$expectedSkylink = 'AQB7zHVDtD-PikoAD_0zzFbWWPcY-IJoJRHXFJcwoU-WvQ';

		$siaPublicKey = newEd25519PublicKey( $publicKey );
		$skylink      = newSkylinkV2( $siaPublicKey, hexToUint8Array( $dataKey ) );

		expect( $skylink->toString() )->toEqual( $expectedSkylink );
	}

	public function testSiaSkylink_fromBytes_ShouldFailOnInvalidInputByteArrayLength() {
		Verify::Callable( function () {
			SiaSkylink::fromBytes( new Uint8Array() );
		} )->throws( Exception::class, "Expected 'parameter', 'data', to be type 'Uint8Array' of length 34, was length 0, was type 'object', value Skynet\Uint8Array::__set_state(array(
   'data' => 
  array (
  ),
   'maxLength' => 0,
   'allowInt' => false,
   'littleIndian' => false,
))" );
	}

	public function testSiaSkylink_fromString_ShouldGetTheSkylinkXFromStringX() {
		foreach ( $this->skylinks as $item ) {
			expect( SiaSkylink::fromString( $item[1] ) )->toEqual( $item[1] );
		}
	}

	public function testSiaSkylink_toString_ShouldConvertTheSkylinkXToStringX() {
		foreach ( $this->skylinks as $item ) {
			expect( $item[0]->toString() )->toEqual( $item[0] );
		}
	}

	protected function _before() {
		$this->expectedBytes = Uint8Array::from( [
			92,
			0,
			111,
			139,
			178,
			109,
			37,
			180,
			18,
			48,
			7,
			3,
			194,
			117,
			39,
			154,
			157,
			133,
			40,
			51,
			227,
			131,
			207,
			237,
			77,
			49,
			79,
			224,
			28,
			12,
			75,
			21,
			93,
			18,
		] );
		$this->skylinks      = [
			[
				new SiaSkylink( 1, hexToUint8Array( 'a0db12bf2960b0c989d5f64bedd3c9c16d5c0ed3430af411d0d0db3de4938ef2' ) ),
				'AQCg2xK_KWCwyYnV9kvt08nBbVwO00MK9BHQ0Ns95JOO8g',
			],
			[
				new SiaSkylink( 1, hexToUint8Array( 'fda409fe5fb07b52647bf21f092b1748f34e1fc01ae269bcc743e0b10dbff12a' ) ),
				'AQD9pAn-X7B7UmR78h8JKxdI804fwBriabzHQ-CxDb_xKg',
			],
		];
		parent::_before(); // TODO: Change the autogenerated stub
	}
}
