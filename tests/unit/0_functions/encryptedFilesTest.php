<?php

use Codeception\MockeryModule\Test;
use Codeception\Verify\Verify;
use Skynet\MySky;
use Skynet\Types\EncryptedFileMetadata;
use Skynet\Uint8Array;
use function Skynet\functions\encrypted_files\checkPaddedBlock;
use function Skynet\functions\encrypted_files\decryptJSONFile;
use function Skynet\functions\encrypted_files\deriveEncryptedFileKeyEntropy;
use function Skynet\functions\encrypted_files\deriveEncryptedFileTweak;
use function Skynet\functions\encrypted_files\encodeEncryptedFileMetadata;
use function Skynet\functions\encrypted_files\encryptJSONFile;
use function Skynet\functions\encrypted_files\padFileSize;
use function Skynet\functions\mysky\deriveEncryptedFileSeed;
use function Skynet\functions\options\makeEncryptedFileMetadata;
/**
 * @group functions
 */
class EncryptedFilesTest extends Test {

	private Uint8Array $fileData;
	private Uint8Array $key;
	private EncryptedFileMetadata $metadata;
	private stdClass $json;
	private string $encryptedTestFilePath = __DIR__ . '/../../_data/encrypted-json-file';

	private int $kib = 1 << 10;
	private int $mib = 1 << 20;
	private int $gib = 1 << 30;
	private array $padFileSize_sizes;
	private array $checkPaddedBlock_sizes;

	public function testDeriveEncryptedFileKeyEntropy_ShouldDeriveTheCorrectEncryptedFileKeyEntropy() {
		$pathSeed        = str_repeat( 'a', 64 );
		$expectedEntropy = [
			145,
			247,
			132,
			82,
			184,
			94,
			1,
			97,
			214,
			174,
			84,
			50,
			40,
			0,
			247,
			144,
			106,
			110,
			227,
			25,
			193,
			138,
			249,
			233,
			32,
			94,
			186,
			244,
			48,
			171,
			115,
			171,
		];
		$result          = deriveEncryptedFileKeyEntropy( $pathSeed );
		expect( $result->compare( Uint8Array::from( $expectedEntropy ) ) )->toBeTrue();
	}

	public function testDeriveEncryptedFileKeyEntropy_ShouldThrowForAnEmptyInputSubPath() {
		$pathSeed = str_repeat( 'a', 64 );
		$subPath  = '';
		Verify::Callable( function () use ( $subPath, $pathSeed ) {
			deriveEncryptedFileSeed( $pathSeed, $subPath, false );
		} )->throws( Exception::class, "Input subPath '' not a valid path" );
	}

	public function testDeriveEncryptedFileTweak_ShouldDeriveTheCorrectEncryptedFileTweak() {
		$seed          = 'test.hns/foo';
		$expectedTweak = '352140f347807438f8f74edf3e0750a408f39b9f2ae4147eb9055d396b467fc8';

		$result = deriveEncryptedFileTweak( $seed );

		expect( $result )->toEqual( $expectedTweak );
	}

	public function testDecryptJSONFile_ShouldDecryptTheGivenTestData() {
		expect( strlen( $this->fileData ) )->toEqual( 4096 );

		$result = decryptJSONFile( $this->fileData, $this->key );

		expect( $result )->toEqual( $this->json );
	}

	public function testDecryptJSONFile_ShouldFailToDecryptBadData() {
		Verify::Callable( function () {
			decryptJSONFile( new Uint8Array( 4096 ), $this->key );
		} )->throws( Exception::class, "Received unrecognized JSON response version '0' in metadata, expected '1'" );
	}

	public function testDecryptJSONFile_ShouldFailToDecryptDataWithACorruptedNonce() {
		$data    = $this->fileData->slice( 0 );
		$data[0] = $data[0] + 1;
		Verify::Callable( function () use ( $data ) {
			decryptJSONFile( $data, $this->key );
		} )->throws( Exception::class, "Could not decrypt given encrypted JSON file" );
	}

	public function testDecryptJSONFile_ShouldFailToDecryptDataWithACorruptedMetadatae() {
		$data                                   = $this->fileData->slice( 0 );
		$data[ MySky::ENCRYPTION_NONCE_LENGTH ] = $data[ MySky::ENCRYPTION_NONCE_LENGTH ] + 1;
		Verify::Callable( function () use ( $data ) {
			decryptJSONFile( $data, $this->key );
		} )->throws( Exception::class, "Received unrecognized JSON response version '2' in metadata, expected '1'" );
	}

	public function testDecryptJSONFile_ShouldFailToDecryptDataThatWasNotPaddedCorrectly() {
		$data = $this->fileData->slice( 0, $this->fileData->getMaxLength() - 1 );
		expect( $data->getMaxLength() )->toEqual( 4095 );
		Verify::Callable( function () use ( $data ) {
			decryptJSONFile( $data, $this->key );
		} )->throws( Exception::class, "Expected parameter 'data' to be padded encrypted data, length was '4095', nearest padded block is '4096'" );
	}

	public function testEncryptJSONFile_ShouldEncryptJsonData() {
		$result = encryptJSONFile( $this->json, $this->metadata, $this->key );

		expect( $result->getMaxLength() )->toEqual( 4096 );
	}

	public function testEncodeEncryptedFileMetadata() {
		$versions = [ 256, - 1 ];

		foreach ( $versions as $version ) {
			$metadata = makeEncryptedFileMetadata( [ 'version' => $version ] );
			Verify::Callable( function () use ( $metadata ) {
				encodeEncryptedFileMetadata( $metadata );
			} )->throws( Exception::class, sprintf( "Metadata version '%d' could not be stored in a uint8", $version ) );
		}
	}

	public function testPadFileSize_ShouldPadTheFileSizeXToX() {
		foreach ( $this->padFileSize_sizes as $size ) {
			$newSize = padFileSize( $size[0] );
			expect( $newSize )->toEqual( $size[1] );
			expect( checkPaddedBlock( $newSize ) )->toBeTrue();
		}
	}

	/*	public function testPadFileSize_ShouldThrowOnAReallyBigNumber(){
			$MAX_SAFE_INTEGER = 0;
			for ($e = 1; ( 2 ** $e ) - ($MAX_SAFE_INTEGER = ( ( 2 ** ( $e - 1 ) ) - 1) * 2 + 1) == 1; $e++) {
				if ($e > 999) {
					throw new Exception("Maximum integer couldn't be found.");
				}
			}
			Verify::Callable( function () use ( $MAX_SAFE_INTEGER ) {
				padFileSize($MAX_SAFE_INTEGER);
			} )->throws( Exception::class, 'Could not pad file size, overflow detected.' );

		}*/

	public function testCheckPaddedBlock_CheckpaddedblockCShouldReturnX() {
		foreach ( $this->checkPaddedBlock_sizes as $size ) {
			expect( checkPaddedBlock( $size[0] ) )->toEqual( $size[1] );
		}
	}

	protected function _before() {
		$this->json     = (object) [ 'message' => 'text' ];
		$this->metadata = new EncryptedFileMetadata( [ 'version' => MySky::ENCRYPTED_JSON_RESPONSE_VERSION ] );
		$this->key      = new Uint8Array( MySky::ENCRYPTION_KEY_LENGTH );
		$this->fileData = Uint8Array::from( file_get_contents( $this->encryptedTestFilePath ) );

		$this->padFileSize_sizes      = [
			[ 1 * $this->kib, 4 * $this->kib ],
			[ 4 * $this->kib, 4 * $this->kib ],
			[ 5 * $this->kib, 8 * $this->kib ],
			[ 105 * $this->kib, 112 * $this->kib ],
			[ 305 * $this->kib, 320 * $this->kib ],
			[ 351 * $this->kib, 352 * $this->kib ],
			[ 352 * $this->kib, 352 * $this->kib ],
			[ $this->mib, $this->mib ],
			[ 100 * $this->mib, 104 * $this->mib ],
			[ $this->gib, $this->gib ],
			[ 100 * $this->gib, 104 * $this->gib ],
		];
		$this->checkPaddedBlock_sizes = [
			[ 1 * $this->kib, false ],
			[ 4 * $this->kib, true ],
			[ 5 * $this->kib, false ],
			[ 105 * $this->kib, false ],
			[ 305 * $this->kib, false ],
			[ 351 * $this->kib, false ],
			[ 352 * $this->kib, true ],
			[ $this->mib, true ],
			[ 100 * $this->mib, false ],
			[ $this->gib, true ],
			[ 100 * $this->gib, false ],
		];

	}
}
