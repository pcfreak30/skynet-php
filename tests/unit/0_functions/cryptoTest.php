<?php

use _support\BaseTest;
use BN\BN;
use Codeception\MockeryModule\Test;
use Codeception\Specify;
use Skynet\Types\RegistryEntry;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\deriveChildSeed;
use function Skynet\functions\crypto\genKeyPairAndSeed;
use function Skynet\functions\crypto\genKeyPairFromSeed;
use function Skynet\functions\crypto\hashDataKey;
use function Skynet\functions\crypto\hashRegistryEntry;
use function Skynet\functions\formatting\toHexString;
/**
 * @group functions
 */
class cryptoTest extends BaseTest {
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	public function testDeriveChildSeed_ShouldCorrectlyDeriveAChildSeed() {
		$masterSeed = 'c1197e1275fbf570d21dde01a00af83ed4a743d1884e4a09cebce0dd21ae254c';
		$seed       = 'seed';
		$expected   = '6140d0d1d8f9e2b759ca7fc96ad3620cd382189f8d46339737e26a2764122b99';

		$childSeed = deriveChildSeed( $masterSeed, $seed );
		expect( $childSeed )->toEqual( $expected );

		$seed1 = deriveChildSeed( $masterSeed, 'asd' );
		$seed2 = deriveChildSeed( $masterSeed, 'aa' );
		$seed3 = deriveChildSeed( $masterSeed, 'ds' );

		expect( $seed1 )->notToEqual( $seed2 );
		expect( $seed2 )->notToEqual( $seed3 );
	}

	public function testGenKeyPairAndSeed_ShouldCreateASeedOfTheGivenLengthHexencoded() {
		$length = 8;
		$seed   = genKeyPairAndSeed( $length )->getSeed();
		expect( strlen( $seed ) )->toEqual( $length * 2 );
	}

	public function testGenKeyPairAndSeed_ShouldCreateAnExpectedKeypairFromAGivenSeed() {
		$seed               = "c1197e1275fbf570d21dde01a00af83ed4a743d1884e4a09cebce0dd21ae254c";
		$expectedPublicKey  = "f8a7da8324fabb9d57bb32c59c48d4ba304d08ee5f1297a46836cf841da71c80";
		$expectedPrivateKey =
			"c404ff07fba961000dfb25ece7477f45b109b50a5169a45f3fb239343002c1cff8a7da8324fabb9d57bb32c59c48d4ba304d08ee5f1297a46836cf841da71c80";

		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairFromSeed( $seed );
		expect( $publicKey )->toEqual( $expectedPublicKey );
		expect( $privateKey )->toEqual( $expectedPrivateKey );
	}

	public function testhashDataKey_ShouldCreateAnExpectedKeypairFromAGivenSeed() {
		$keys = [
			[ "", "81e47a19e6b29b0a65b9591762ce5143ed30d0261e5d24a3201752506b20f15c" ],
			[ "skynet", "31c7a4d53ef7bb4c7531181645a0037b9e75c8b1d1285b468ad58bad6262c777" ],
		];

		foreach ( $keys as $key ) {
			expect( toHexString( hashDataKey( $key[0] )->toString() ) )->toEqual( $key[1] );
		}
	}

	public function testHashRegistryValue_ShouldMatchSiadForEqualInput() {
		$h    = '788dddf5232807611557a3dc0fa5f34012c2650526ba91d55411a2b04ba56164';
		$hash = hashRegistryEntry(
			new RegistryEntry(
				'HelloWorld',
				Uint8Array::from( 'abc' ),
				new BN( 123456789 ) ),
			false
		);

		expect( toHexString( $hash ) )->toEqual( $h );
	}

	public function testHashRegistryValue_ShouldMatchSiadForEqualInputWhenDataKeyAndDataIncludeUnicode() {
		$h    = 'ff3b430675a0666e7461bc34aec9f66e21183d061f0b8232dd28ca90cc6ea5ca';
		$hash = hashRegistryEntry(
			new RegistryEntry(
				'HelloWorld π',
				Uint8Array::from( 'abc π' ),
				new BN( 123456789 ) ),
			false
		);

		expect( toHexString( $hash ) )->toEqual( $h );
	}

	public function testHashDataKey_HashingRegistryEntryWithHashedVsNonhashedDatakeysShouldResultInDifferentOutputs() {
		$hash1 = hashRegistryEntry(
			new RegistryEntry(
				'abcd',
				Uint8Array::from( 'abc π' ),
				new BN( 123456789 ) ),
			false
		);

		$hash2 = hashRegistryEntry(
			new RegistryEntry(
				'abcd',
				Uint8Array::from( 'abc π' ),
				new BN( 123456789 ) ),
			false
		);

		expect( toHexString( $hash1 ) )->notToEqual( $hash2 );
	}

	protected function _before() {
	}

	// tests

	protected function _after() {
	}
}
