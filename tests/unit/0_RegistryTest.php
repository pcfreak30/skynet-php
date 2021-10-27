<?php

use _support\BaseTest;
use BN\BN;
use Codeception\Verify\Verify;
use Skynet\Registry;
use Skynet\Types\RegistryEntry;
use Skynet\Types\RegistryProof;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\genKeyPairAndSeed;
use function Skynet\functions\crypto\genKeyPairFromSeed;
use function Skynet\functions\options\makeGetEntryOptions;
use function Skynet\functions\registry\getEntryLink;
use function Skynet\functions\registry\getEntryUrlForPortal;
use function Skynet\functions\registry\signEntry;
use function Skynet\functions\registry\validateRegistryProof;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;
use const Skynet\DEFAULT_SKYNET_PORTAL_URL;
use const Skynet\URI_SKYNET_PREFIX;
/**
 * @group mainUnit
 */
class RegistryTest extends BaseTest {
	private string $registryLookupUrl;
	private string $publicKey;
	private string $privateKey;
	private string $dataKey = 'app';
	private Registry $registry;

	private $getEntryURL_publicKey = 'c1197e1275fbf570d21dde01a00af83ed4a743d1884e4a09cebce0dd21ae254c';
	private $getEntryURL_encodedPK = 'ed25519%3Ac1197e1275fbf570d21dde01a00af83ed4a743d1884e4a09cebce0dd21ae254c';
	private $getEntryURL_encodedDK = '7c96a0537ab2aaac9cfe0eca217732f4e10791625b4ab4c17e4d91c8078713b9';

	public function testGetEntry_ShouldThrowIfTheResponseStatusIsNotInThe200sAndNot404AndJsonIsReturned() {
		$this->getRequestMockBadRequestReturn( [ 'message' => 'foo error' ], [], [ $this->registryLookupUrl ] );
		Verify::Callable( function () {
			$this->registry->getEntry( $this->publicKey, $this->dataKey );
		} )->throws( Exception::class, 'Request failed with status code 400' );

	}

	public function testGetEntry_ShouldThrowIfTheResponseStatusIsNotInThe200sAndNot404AndHtmlIsReturned() {
		$this->getRequestMockWithReturn( $this->buildRequestResponse( 429,
			'<head><title>429 Too Many Requests</title></head>
<body>
<center><h1>429 Too Many Requests</h1></center>
<hr><center>openresty/1.19.3.1</center>
</body>
</html>', [] ), [ $this->registryLookupUrl ] );
		Verify::Callable( function () {
			$this->registry->getEntry( $this->publicKey, $this->dataKey );
		} )->throws( Exception::class, 'Request failed with status code 429' );

	}

	public function testGetEntry_ShouldThrowIfTheSignatureCouldNotBeVerified() {
		$entryData = [
			'data'      => '43414241425f31447430464a73787173755f4a34546f644e4362434776744666315579735f3345677a4f6c546367',
			'revision'  => 11,
			'signature' => '33d14d2889cb292142614da0e0ff13a205c4867961276001471d13b779fc9032568ddd292d9e0dff69d7b1f28be07972cc9d86da3cecf3adecb6f9b7311af808',
		];
		$this->getRequestMockSucessfulReturn( $entryData, [], [ $this->registryLookupUrl ] );
		Verify::Callable( function () {
			$this->registry->getEntry( $this->publicKey, $this->dataKey );
		} )->throws( Exception::class, 'Could not verify signature from retrieved, signed registry entry -- possible corrupted entry' );
	}

	public function testGetEntry_ShouldThrowAnErrorIfThePublicKeyIsNotHexencoded() {
		Verify::Callable( function () {
			$this->registry->getEntry( "foo", $this->dataKey );
		} )->throws( Exception::class, "Expected 'parameter', 'publicKey', to be a hex-encoded string with a valid prefix, was type 'string', value foo" );
	}

	public function testGetEntry_ShouldThrowOnIncompleteResponseFromRegistryGet() {
		$this->getRequestMockSucessfulReturn( '{}', [], [ $this->registryLookupUrl ] );
		Verify::Callable( function () {
			$this->registry->getEntry( $this->publicKey, $this->dataKey );
		} )->throws( Exception::class, "Did not get a complete entry response despite a successful request. Please try again and report this issue to the devs if it persists. Error: Expected 'entry response field', 'response->body->data', to be type 'string', was type 'null'" );
	}

	public function testGetEntryLink_ShouldGetTheCorrectEntryLink() {
		$publicKey         = 'a1790331b8b41a94644d01a7b482564e7049047812364bcabc32d399ad23f7e2';
		$dataKey           = 'd321b3c31337047493c9b5a99675e9bdaea44218a31aad2fd7738209e7a5aca1';
		$expectedEntryLink = URI_SKYNET_PREFIX . 'AQB7zHVDtD-PikoAD_0zzFbWWPcY-IJoJRHXFJcwoU-WvQ';

		$entryLink = getEntryLink( $publicKey, $dataKey, makeGetEntryOptions( [ 'hashedDataKeyHex' => true ] ) );

		expect( $entryLink )->toEqual( $expectedEntryLink );
	}

	/*	public function testGetEntryUrl_ShouldGenerateTheCorrectRegistryUrlForTheGivenEntry() {
			[ 'publicKey' => $publicKey ] = genKeyPairAndSeed();

			Verify::Callable( function () {
				$this->registry->getEntry( $this->publicKey, $this->dataKey );
			} )->throws( Exception::class, "Did not get a complete entry response despite a successful request. Please try again and report this issue to the devs if it persists. Error: Expected 'entry response field', 'response->body->data', to be type 'string', was type 'null'" );
		}	*/

	public function testGetEntryUrl_ShouldGenerateTheCorrectRegistryUrlForTheGivenEntry() {
		$url = $this->registry->getEntryUrl( $this->getEntryURL_publicKey, $this->dataKey );

		expect( $url )->toEqual( sprintf( '%s/skynet/registry?publickey=%s&datakey=%s&timeout=5', $this->portalUrl, $this->getEntryURL_encodedPK, $this->getEntryURL_encodedDK ) );
	}

	public function testGetEntryUrl_ShouldTrimThePrefixIfItIsProvided() {
		$url = $this->registry->getEntryUrl( "ed25519:{$this->getEntryURL_publicKey}", $this->dataKey );

		expect( $url )->toEqual( sprintf( '%s/skynet/registry?publickey=%s&datakey=%s&timeout=5', $this->portalUrl, $this->getEntryURL_encodedPK, $this->getEntryURL_encodedDK ) );
	}

	public function testSetEntry_ShouldThrowAnErrorIfThePrivateKeyIsNotHexencoded() {
		Verify::Callable( function () {
			$this->registry->setEntry( 'foo', new RegistryEntry( '', new Uint8Array(), new BN( 0 ) ) );
		} )->throws( Exception::class, "Expected 'parameter', 'privateKey', to be a hex-encoded string, was type 'string', value foo" );

	}

	public function testSignEntry_HouldThrowIfWeTryToSignAnEntryWithAPrehashedDataKeyThatIsNotInHexFormat() {
		Verify::Callable( function () {
			$entry = new RegistryEntry( 'test', stringToUint8ArrayUtf8( 'test' ), new BN( 0 ) );
			signEntry( $this->privateKey, $entry, true );
		} )->throws( Exception::class, "Expected 'parameter', 'str', to be a hex-encoded string, was type 'string', value test" );

	}

	public function testShouldVerifyAValidRegistryProof() {
		$proof = new RegistryProof( [
			'data'      => '5c006f8bb26d25b412300703c275279a9d852833e383cfed4d314fe01c0c4b155d12',
			'revision'  => 0,
			'datakey'   => '43c8a9b01609544ab152dad397afc3b56c1518eb546750dbc6cad5944fec0292',
			'publickey' => (object) [
				'algorithm' => 'ed25519',
				'key'       => 'y/l99FyfFm6JPhZL5xSkruhA06Qh9m5S9rnipQCc+rw=',
			],
			'signature' => '5a1437508eedb6f5352d7f744693908a91bb05c01370ce4743de9c25f761b4e87760b8172448c073a4ddd9d58d1a2bf978b3227e57e4fa8cbe830a2353be2207',
			'type'      => 1,
		] );

		$expectedSkylink         = 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';
		$expectedResolverSkylink = 'AQDwh1jnoZas9LaLHC_D4-2yP9XYDdZzNtz62H4Dww1jDA';

		[ 'skylink' => $skylink, 'resolverSkylink' => $resolverSkylink ] = validateRegistryProof( [ $proof ] );

		expect( $skylink )->toEqual( $expectedSkylink );
		expect( $resolverSkylink )->toEqual( $expectedResolverSkylink );
	}

	protected function _before() {
		parent::_before();
		$this->portalUrl = DEFAULT_SKYNET_PORTAL_URL;
		[
			'publicKey'  => $this->publicKey,
			'privateKey' => $this->privateKey,
		] = genKeyPairFromSeed( "insecure test seed" );
		$this->registryLookupUrl = getEntryUrlForPortal( $this->portalUrl, $this->publicKey, $this->dataKey );
		$this->registry          = new Registry( $this->client );
		$this->registry->setHttpClient( $this->requestsMock);
	}
}
