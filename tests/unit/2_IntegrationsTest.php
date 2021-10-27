<?php

use BN\BN;
use Codeception\Test\Unit;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use Skynet\Db;
use Skynet\MySky;
use Skynet\Skynet;
use Skynet\Types\File;
use Skynet\Types\KeyPairAndSeed;
use Skynet\Types\RegistryEntry;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\genKeyPairAndSeed;
use function Skynet\functions\crypto\hashDataKey;
use function Skynet\functions\formatting\convertSkylinkToBase64;
use function Skynet\functions\formatting\decodeSkylinkBase64;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\options\makeGetJSONOptions;
use function Skynet\functions\options\makeSetJSONOptions;
use function Skynet\functions\options\makeUploadOptions;
use function Skynet\functions\registry\getEntryLink;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;
use const Skynet\DEFAULT_SKYNET_PORTAL_URL;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @group integration
 */
class IntegrationsTest extends Unit {
	private MySky $client;
	private string $path = 'snew.hns/asdf';
	private string $pathSeed = 'fe2c5148646532a442dd117efab3ff2a190336da506e363f80fb949513dab811';
	private string $userId = '89e5147864297b80f5ddf29711ba8c093e724213b0dcbefbc3860cc6d598cc35';
	private string $enc_userId = '4dfb9ce035e4e44711c1bb0a0901ce3adc2a928b122ee7b45df6ac47548646b0';
	private string $skydb_publicKey = '89e5147864297b80f5ddf29711ba8c093e724213b0dcbefbc3860cc6d598cc35';
	private string $skydb_expectedDataLink = URI_SKYNET_PREFIX . 'AACDPHoC2DCV_kLGUdpdRJr3CcxCmKadLGPi6OAMl7d48w';
	private string $dataKey = 'HelloWorld';
	private string $registry_skylink = 'AABRKCTb6z9d-C-Hre-daX4-VIB8L7eydmEr8XRphnS8jg';
	private Uint8Array $registry_data;
	private string $upload_fileData = 'testing';
	private array $upload_json = [ 'key' => 'testdownload' ];
	private string $upload_plaintextType = 'text/plain';
	private stdClass $upload_plaintextMetadata;
	private string $portal = DEFAULT_SKYNET_PORTAL_URL;


	public function testFileApiIntegrationTests_ShouldGetExistingFileApiJsonData() {
		$expected = [ 'name' => 'testnames' ];

		[ 'data' => $recieved ] = $this->client->getJSON( $this->path, $this->userId );
		expect( $recieved->name )->toEqual( $expected['name'] );
	}

	public function testFileApiIntegrationTests_ShouldGetExistingFileApiEntryData() {
		$expected = Uint8Array::from( [
			65,
			65,
			67,
			116,
			77,
			77,
			114,
			101,
			122,
			76,
			56,
			82,
			71,
			102,
			105,
			98,
			104,
			67,
			53,
			79,
			98,
			120,
			48,
			83,
			102,
			69,
			106,
			48,
			77,
			87,
			108,
			106,
			95,
			112,
			55,
			97,
			95,
			77,
			107,
			90,
			85,
			81,
			45,
			77,
			57,
			65,
		] );
		$userID   = '89e5147864297b80f5ddf29711ba8c093e724213b0dcbefbc3860cc6d598cc35';
		$path     = 'snew.hns/asdf';
		[ 'data' => $recieved ] = $this->client->getEntryData( $path, $userID );
		expect( $recieved->compare( $expected ) )->toBeTrue();
	}

	public function testFileApiIntegrationTests_GetentrydataShouldReturnNullForNonexistentFileApiEntryData() {
		[ 'publicKey' => $userId ] = genKeyPairAndSeed();
		[ 'data' => $received ] = $this->client->getEntryData( $this->path, $userId );

		expect( $received )->toBeNull();
	}

	public function testFileApiIntegrationTests_ShouldGetAnExistingEntryLinkForAUserIdAndPath() {
		$expected = URI_SKYNET_PREFIX . 'AQAKDRJbfAOOp3Vk8L-cjuY2d34E8OrEOy_PTsD0xCkYOQ';

		$entryLink = $this->client->getEntryLink( $this->path, $this->userId );
		expect( $entryLink )->toEqual( $expected );
	}

	public function testEncryptedFileApiIntegrationTests_ShouldGetExistingEncryptedJson() {
		$expectedJson = (object) [ 'message' => 'foo' ];
		[ 'data' => $data ] = $this->client->getJSONEncrypted( $this->pathSeed, $this->enc_userId, true );

		expect( $data )->toEqual( $expectedJson );
	}

	public function testEncryptedFileApiIntegrationTests_ShouldReturnNullForInexistantEncryptedJson() {
		$pathSeed = str_repeat( 'a', 64 );
		[ 'data' => $data ] = $this->client->getJSONEncrypted( $pathSeed, $this->enc_userId, true );

		expect( $data )->toBeNull();
	}

	public function testSkydbEndToEndIntegrationTests_ShouldGetExistingSkydbData() {
		$dataKey      = 'dataKey1';
		$expectedData = (object) [ 'message' => 'hi there' ];

		[
			'data'     => $recieved,
			'dataLink' => $dataLink,
		] = $this->client->getDb()->getJSON( $this->skydb_publicKey, $dataKey );

		expect( $expectedData )->toEqual( $recieved );
		expect( $dataLink )->toEqual( $this->skydb_expectedDataLink );
	}

	public function testSkydbEndToEndIntegrationTests_ShouldGetExistingSkydbDataUsingEntryLink() {
		$dataKey           = 'dataKey3';
		$expectedJson      = (object) [ 'message' => 'hi there!' ];
		$expectedData      = (object) [ '_data' => $expectedJson, '_v' => 2 ];
		$expectedEntryLink = URI_SKYNET_PREFIX . 'AQAZ1R-KcL4NO_xIVf0q8B1ngPVd6ec-Pu54O0Cto387Nw';
		$expectedDataLink  = URI_SKYNET_PREFIX . 'AAAVyJktMuK-7WRCNUvYcYq7izvhCbgDLXlT4YgechblJw';

		$entryLink = getEntryLink( $this->skydb_publicKey, $dataKey );
		expect( $entryLink )->toEqual( $expectedEntryLink );
		[ 'data' => $data ] = $this->client->getSkynet()->getFileContent( $entryLink );

		$data = json_decode( $data );
		expect( $data )->toEqual( $expectedData );

		[
			'data'     => $json,
			'dataLink' => $dataLink,
		] = $this->client->getDb()->getJSON( $this->skydb_publicKey, $dataKey );
		expect( $dataLink )->toEqual( $expectedDataLink );
		expect( $json )->toEqual( $expectedJson );
	}

	public function testSkydbEndToEndIntegrationTests_GetrawbytesShouldPerformALookupButNotASkylinkGetIfTheCacheddatalinkIsAHitForExistingData() {
		$expectedDataLink = URI_SKYNET_PREFIX . 'AAAVyJktMuK-7WRCNUvYcYq7izvhCbgDLXlT4YgechblJw';
		$dataKey          = 'dataKey3';
		[
			'data'     => $returnedData,
			'dataLink' => $dataLink,
		] = $this->client->getDb()->getRawBytes( $this->skydb_publicKey, $dataKey, makeGetJSONOptions( [ 'cachedDataLink' => $expectedDataLink ] ) );

		expect( $returnedData )->toBeNull();
		expect( $dataLink )->toEqual( $expectedDataLink );
	}

	public function testSkydbEndToEndIntegrationTests_ShouldGetExistingSkydbDataWithUnicodeDataKey() {
		$publicKey = '4a964fa1cb329d066aedcf7fc03a249eeea3cf2461811090b287daaaec37ab36';
		$dataKey   = 'dataKeyż';
		$expected  = (object) [ 'message' => 'Hello' ];

		[ 'data' => $recieved ] = $this->client->getDb()->getJSON( $publicKey, $dataKey );

		expect( $expected )->toEqual( $recieved );
	}

	public function testSkydbEndToEndIntegrationTests_ShouldReturnNullForAnInexistentEntry() {
		[ 'publicKey' => $publicKey ] = genKeyPairAndSeed();

		[ 'data' => $data, 'dataLink' => $dataLink ] = $this->client->getDb()->getJSON( $publicKey, 'foo' );
		expect( $data )->toBeNull();
		expect( $dataLink )->toBeNull();
	}

	public function testSkydbEndToEndIntegrationTests_ShouldSetAndGetNewEntries() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$json  = (object) [ 'data' => 'thisistext' ];
		$json2 = (object) [ 'data' => 'foo2' ];

		$this->client->getDb()->setJSON( $privateKey, $this->dataKey, $json );

		[ 'data' => $data, 'dataLink' => $dataLink ] = $this->client->getDb()->getJSON( $publicKey, $this->dataKey );
		expect( $data )->toEqual( $json );
		expect( $dataLink )->notToBeEmpty();

		$this->client->getDb()->setJSON( $privateKey, $this->dataKey, $json2 );

		[ 'data' => $data2, 'dataLink' => $dataLink2 ] = $this->client->getDb()->getJSON( $publicKey, $this->dataKey );
		expect( $data2 )->toEqual( $json2 );
		expect( $dataLink2 )->notToBeEmpty();


	}

	public function testSkydbEndToEndIntegrationTests_ShouldBeAbleToSetANewEntryAsDeletedAndThenWriteOverIt() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$this->client->getDb()->deleteJSON( $privateKey, $this->dataKey );
		$entryLink = getEntryLink( $publicKey, $this->dataKey );

		try {
			$this->client->getSkynet()->getFileContent( $entryLink );
			throw new Exception( 'getFileContent should not have succeeded' );
		} catch ( ClientException $e ) {
			expect( $e->getResponse()->getStatusCode() )->toEqual( 404 );
		}

		[ 'data' => $data, 'dataLink' => $dataLink ] = $this->client->getDb()->getJSON( $publicKey, $this->dataKey );

		expect( $data )->toBeNull();
		expect( $dataLink )->toBeNull();

		$json = (object) [ 'data' => 'thisistext' ];

		$this->client->getDb()->setJSON( $privateKey, $this->dataKey, $json );

		[ 'data' => $data2, 'dataLink' => $dataLink2 ] = $this->client->getDb()->getJSON( $publicKey, $this->dataKey );

		expect( $data2 )->toEqual( $json );
		expect( $dataLink2 )->notToBeEmpty();
	}

	public function testSkydbEndToEndIntegrationTests_ShouldCorrectlySetADataLink() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$dataLink      = 'AAAVyJktMuK-7WRCNUvYcYq7izvhCbgDLXlT4YgechblJw';
		$dataLinkBytes = decodeSkylinkBase64( $dataLink );

		$this->client->getDb()->setDataLink( $privateKey, $this->dataKey, $dataLink );

		[ 'entry' => $returnedEntry ] = $this->client->getRegistry()->getEntry( $publicKey, $this->dataKey );

		expect( $returnedEntry )->notToBeNull();
		expect( $returnedEntry )->toBeInstanceOf( RegistryEntry::class );
		expect( $returnedEntry->getData()->compare( $dataLinkBytes ) )->toBeTrue();
	}

	public function testSkydbEndToEndIntegrationTests_ShouldCorrectlyHandleTheHasheddatakeyhexOption() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();
		$dataKey          = 'test';
		$hashedDataKeyHex = toHexString( hashDataKey( $dataKey ) );
		$json             = (object) [ 'message' => 'foo' ];

		$this->client->getDb()->setJSON( $privateKey, $hashedDataKeyHex, $json, makeSetJSONOptions( [ 'hashedDataKeyHex' => true ] ) );

		[ 'data' => $data ] = $this->client->getDb()->getJSON( $publicKey, $dataKey, makeGetJSONOptions( [ 'hashedDataKeyHex' => false ] ) );

		expect( $data )->toEqual( $json );
	}

	public function testRegistryEndToEndIntegrationTests_ShouldReturnNullForAnInexistentEntry() {
		[ 'publicKey' => $publicKey ] = genKeyPairAndSeed();

		[ 'entry' => $entry, 'signature' => $signature ] = $this->client->getRegistry()->getEntry( $publicKey, 'foo' );

		expect( $entry )->toBeNull();
		expect( $signature )->toBeNull();
	}

	public function testRegistryEndToEndIntegrationTests_ShouldSetAndGetStringEntriesCorrectly() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$entry = new RegistryEntry( $this->dataKey, $this->registry_data, new BN( 0 ) );
		$this->client->getRegistry()->setEntry( $privateKey, $entry );

		[ 'entry' => $returnedEntry ] = $this->client->getRegistry()->getEntry( $publicKey, $this->dataKey );
		expect( $returnedEntry )->notToBeNull();
		expect( $returnedEntry )->toEqual( $entry );
	}

	public function testRegistryEndToEndIntegrationTests_ShouldSetAndGetUnicodeEntriesCorrectly() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$entry = new RegistryEntry( $this->dataKey, stringToUint8ArrayUtf8( "∂" ), new BN( 0 ) );
		$this->client->getRegistry()->setEntry( $privateKey, $entry );

		[ 'entry' => $returnedEntry ] = $this->client->getRegistry()->getEntry( $publicKey, $this->dataKey );
		expect( $returnedEntry )->notToBeNull();
		expect( $returnedEntry )->toEqual( $entry );
	}

	public function testRegistryEndToEndIntegrationTests_ShouldSetAndGetAnEntryWithEmptyDataCorrectly() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();

		$entry = new RegistryEntry( $this->dataKey, new Uint8Array(), new BN( 0 ) );
		$this->client->getRegistry()->setEntry( $privateKey, $entry );

		[ 'entry' => $returnedEntry ] = $this->client->getRegistry()->getEntry( $publicKey, $this->dataKey );
		expect( $returnedEntry )->notToBeNull();
		expect( $returnedEntry )->toEqual( $entry );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldGetFileContentForAnExistingEntryLinkOfDepth1() {
		$entryLink        = 'AQDwh1jnoZas9LaLHC_D4-2yP9XYDdZzNtz62H4Dww1jDA';
		$expectedDataLink = URI_SKYNET_PREFIX . 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';

		[ 'skylink' => $skylink ] = $this->client->getSkynet()->getFileContent( $entryLink );

		expect( $skylink )->toEqual( $expectedDataLink );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldGetFileContentForAnExistingEntryLinkOfDepth2() {
		$entryLinkBase32  = '0400mgds8arrfnu8e6b0sde9fbkmh4nl2etvun55m0fvidudsb7bk78';
		$entryLink        = convertSkylinkToBase64( $entryLinkBase32 );
		$expectedDataLink = URI_SKYNET_PREFIX . 'EAAFgq17B-MKsi0ARYKUMmf9vxbZlDpZkA6EaVBCG4YBAQ';

		[ 'skylink' => $skylink ] = $this->client->getSkynet()->getFileContent( $entryLink );
		expect( $skylink )->toEqual( $expectedDataLink );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUploadAndDownloadDirectories() {
		$directory = [
			new File( [ 'data' => Uint8Array::from( 'foo1' ), 'fileName' => 'file1.jpeg', 'filePath' => 'i-am-not' ] ),
			new File( [
				'data'     => Uint8Array::from( 'foo2' ),
				'fileName' => 'i-am-not/file2.jpeg',
				'filePath' => 'i-am-not',
			] ),
			new File( [
				'data'     => Uint8Array::from( 'foo3' ),
				'fileName' => 'i-am-not/me-neither/file3.jpeg',
				'filePath' => 'i-am-not/me-neither',
			] ),
		];

		$dirname = 'dirname';
		$dirType = 'application/zip';

		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadDirectory( $directory, $dirname );

		expect( $skylink )->notToEqual( '' );

		$resp = $this->client->getSkynet()->getFileContent( $skylink );
		[
			'data'        => $data,
			'contentType' => $contentType,
			'portalUrl'   => $portalUrl,
			'skylink'     => $returnedSkylink,
		] = $resp;

		expect( $data )->toBeString();
		expect( $contentType )->toEqual( $dirType );
		expect( $portalUrl )->toEqual( $this->portal );
		expect( $skylink )->toEqual( $returnedSkylink );

	}

	public function testUploadAndDownloadEndtoendTests_CustomFilenamesShouldTakeEffects() {
		$customFilename = 'asdf!!';

		$file = new File( [ 'fileName' => $this->dataKey, 'data' => Uint8Array::from( $this->upload_fileData ) ] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file, makeUploadOptions( [ 'customFilename' => $customFilename ] ) );

		expect( $skylink )->notToEqual( '' );

		[ 'metadata' => $metadata ] = $this->client->getSkynet()->getMetadata( $skylink );

		expect( $metadata->filename )->toEqual( $customFilename );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUploadAndDownloadTwoFilesWithDifferentNamesAndCompareTheirEtags() {
		[ $filename1, $filename2 ] = [ randomUnicodeString( 16 ), randomUnicodeString( 16 ) ];
		$data = 'file';

		[ 'skylink' => $skylink1 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data ),
			'fileName' => $filename1,
		] ) );
		[ 'skylink' => $skylink2 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data ),
			'fileName' => $filename2,
		] ) );

		$this->expectDifferentEtags( $skylink1, $skylink2 );;
	}

	private function expectDifferentEtags( string $link1, string $link2 ): void {
		expect( $link1 )->notToEqual( $link2 );

		$url1 = $this->client->getSkynet()->getSkylinkUrl( $link1 );
		$url2 = $this->client->getSkynet()->getSkylinkUrl( $link2 );


		$response1 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $link1 );
		$response2 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $link2 );

		$etag1 = $response1->getHeaderLine( 'etag' );
		$etag2 = $response2->getHeaderLine( 'etag' );

		expect( $etag1 )->notToBeEmpty();
		expect( $etag2 )->notToBeEmpty();

		expect( $etag1 )->notToEqual( $etag2 );

		$url1 .= '?nocache=true';
		$url2 .= '?nocache=true';

		$response3 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $url1 );
		$response4 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $url2 );

		$etag3 = $response1->getHeaderLine( 'etag' );
		$etag4 = $response2->getHeaderLine( 'etag' );

		expect( $etag3 )->toEqual( $etag1 );
		expect( $etag4 )->toEqual( $etag2 );
	}

	private function callPrivate( $object, $method, ...$args ) {
		$reflect = new ReflectionClass( $object );
		$method  = $reflect->getMethod( $method );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $args );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUploadAndDownloadTwoFilesWithDifferentContentsAndCompareTheirEtags() {
		[ $data1, $data2 ] = [ randomUnicodeString( 16 ), randomUnicodeString( 16 ) ];
		$filename = 'file';

		[ 'skylink' => $skylink1 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data1 ),
			'fileName' => $filename,
		] ) );
		[ 'skylink' => $skylink2 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data2 ),
			'fileName' => $filename,
		] ) );

		$this->expectDifferentEtags( $skylink1, $skylink2 );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUpdateAnEtagForAResolverSkylinkAfterChangingItsData() {
		[ 'publicKey' => $publicKey, 'privateKey' => $privateKey ] = genKeyPairAndSeed();
		[ $data1, $data2 ] = [ randomUnicodeString( 4096 ), randomUnicodeString( 4096 ) ];
		$filename = 'file';

		$dataKey   = randomUnicodeString( 16 );
		$entryLink = getEntryLink( $publicKey, $dataKey );

		[ 'skylink' => $skylink1 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data1 ),
			'fileName' => $filename,
		] ) );
		[ 'skylink' => $skylink2 ] = $this->client->getSkynet()->uploadFile( new File( [
			'data'     => Uint8Array::from( $data2 ),
			'fileName' => $filename,
		] ) );

		$this->client->getDb()->setDataLink( $privateKey, $dataKey, $skylink1 );;

		$url = $this->client->getSkynet()->getSkylinkUrl( $entryLink );

		$response1 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $url );
		$etag1     = $response1->getHeaderLine( 'etag' );
		expect( $etag1 )->notToBeEmpty();

		$this->client->getDb()->setDataLink( $privateKey, $dataKey, $skylink2 );;

		$response1 = $this->callPrivate( $this->client->getSkynet(), 'getFileContentRequest', $url );
		$etag2     = $response1->getHeaderLine( 'etag' );
		expect( $etag2 )->notToBeEmpty();
		expect( $etag2 )->notToEqual( $etag1 );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldGetPlaintextFileContents() {
		$file = new File( [
			'data'     => Uint8Array::from( $this->upload_fileData ),
			'fileName' => $this->dataKey,
			'mime'     => $this->upload_plaintextType,
		] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );

		expect( $skylink )->notToBeEmpty();

		$content = $this->client->getSkynet()->getFileContent( $skylink );
		expect( $content->getData() )->toEqual( $this->upload_fileData );
		expect( $content->getContentType() )->toEqual( $this->upload_plaintextType );
		expect( $content->getPortalUrl() )->toEqual( $this->portal );
		expect( $skylink )->toEqual( $content->getSkylink() );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldGetPlaintextFileMetadata() {
		$file = new File( [
			'data'     => Uint8Array::from( $this->upload_fileData ),
			'fileName' => $this->dataKey,
			'mime'     => $this->upload_plaintextType,
		] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );
		expect( $skylink )->notToBeEmpty();

		$metadata = $this->client->getSkynet()->getMetadata( $skylink );

		expect( $metadata->getMetadata() )->toEqual( $this->upload_plaintextMetadata );
		expect( $metadata->getPortalUrl() )->toEqual( $this->portal );
		expect( $metadata->getSkylink() )->toEqual( $skylink );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldGetJsonFileContents() {
		$file = new File( [
			'data'     => Uint8Array::from( json_encode( $this->upload_json ) ),
			'fileName' => $this->dataKey,
			'mime'     => 'application/json',
		] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );
		expect( $skylink )->notToBeEmpty();

		[ 'data' => $data, 'contentType' => $contentType ] = $this->client->getSkynet()->getFileContent( $skylink );
		$data = json_decode( $data );
		expect( $data )->toBeObject();
		expect( $data )->toEqual( (object) $this->upload_json );
		expect( $contentType )->toEqual( 'application/json' );
	}

	/*	public function testUploadAndDownloadEndtoendTests_ShouldGetFileContentsWhenContentTypeIsNotSpecifiedButInferredFromFilename() {
			$file = new File( [
				'data'     => Uint8Array::from( json_encode( $this->upload_json ) ),
				'fileName' => $this->dataKey . '.json'
			] );
			[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );
			expect( $skylink )->notToBeEmpty();
			[ 'data' => $data, 'contentType' => $contentType ] = $this->client->getSkynet()->getFileContent( $skylink );

			$data = json_decode( $data );
			expect( $data )->toBeObject();
			expect( $data )->toEqual( (object) $this->upload_json );
			expect( $contentType )->toEqual( 'application/json' );
		}*/

	public function testUploadAndDownloadEndtoendTests_ShouldGetFileContentsWhenContentTypeIsNotSpecified() {
		$file = new File( [
			'data'     => Uint8Array::from( json_encode( $this->upload_json ) ),
			'fileName' => $this->dataKey,
		] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );
		expect( $skylink )->notToBeEmpty();
		[ 'data' => $data, 'contentType' => $contentType ] = $this->client->getSkynet()->getFileContent( $skylink );

		$data = json_decode( $data );
		expect( $data )->toBeObject();
		expect( $data )->toEqual( (object) $this->upload_json );
		expect( $contentType )->toEqual( 'application/octet-stream' );
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUploadAndDownloadA0byteFile() {
		$file = new File( [ 'data' => new Uint8Array(), 'fileName' => $this->dataKey ] );
		expect( $file->getFileSize() )->toEqual( 0 );

		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );

		expect( $skylink )->notToBeEmpty();

		[ 'data' => $data ] = $this->client->getSkynet()->getFileContent( $skylink );

		expect( $data )->toBeEmpty();
	}

	public function testUploadAndDownloadEndtoendTests_ShouldUploadAndDownloadA1byteFile() {
		$filedata = 'a';
		$file     = new File( [ 'data' => Uint8Array::from( $filedata ), 'fileName' => $this->dataKey ] );
		expect( $file->getFileSize() )->toEqual( 1 );

		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );

		expect( $skylink )->notToBeEmpty();

		[ 'data' => $data ] = $this->client->getSkynet()->getFileContent( $skylink );

		expect( $data )->toEqual( $filedata );
	}

	public function testPinSkylink_ShouldCallTheActualPinEndpointAndGetTheSkylinkFromTheHeaders() {
		$file = new File( [ 'data' => Uint8Array::from( $this->upload_fileData ), 'fileName' => $this->dataKey ] );
		[ 'skylink' => $skylink ] = $this->client->getSkynet()->uploadFile( $file );
		expect( $skylink )->notToBeEmpty();

		[ 'skylink' => $skylink2 ] = $this->client->getSkynet()->pinSkylink( $skylink );

		expect( $skylink )->toEqual( $skylink2 );
	}

	public function testResolveHns_ShouldResolveAnHnsNameWithAnUnderlyingSkynsLinkToASkylink() {
		$domain            = 'mayonnaise';
		$expectedEntryLink = URI_SKYNET_PREFIX . 'AQDwh1jnoZas9LaLHC_D4-2yP9XYDdZzNtz62H4Dww1jDA';
		$dataKey           = '43c8a9b01609544ab152dad397afc3b56c1518eb546750dbc6cad5944fec0292';
		$publicKey         = 'cbf97df45c9f166e893e164be714a4aee840d3a421f66e52f6b9e2a5009cfabc';

		$expectedData = (object) [
			'registry' => (object) [
				'publickey' => "ed25519:{$publicKey}",
				'datakey'   => $dataKey,
			],
		];

		[ 'data' => $data, 'skylink' => $skylink ] = $this->client->getSkynet()->resolveHns( $domain );

		expect( $skylink )->toEqual( $expectedEntryLink );
		expect( $data )->toEqual( $expectedData );

	}

	protected function _before() {
		$this->client                   = new MySky();
		$this->registry_data            = stringToUint8ArrayUtf8( $this->registry_skylink );
		$this->upload_plaintextMetadata = (object) [
			'filename' => $this->dataKey,
			'length'   => strlen( $this->upload_fileData ),
			'subfiles' => (object) [
				'HelloWorld' => (object) [
					'filename'    => $this->dataKey,
					'contenttype' => $this->upload_plaintextType,
					'len'         => strlen( $this->upload_fileData ),
				],
			],
			'tryfiles' => [ 'index.html' ],
		];
	}
}
