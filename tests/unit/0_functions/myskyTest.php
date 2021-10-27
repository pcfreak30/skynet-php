<?php

namespace unit\functions;


use Codeception\MockeryModule\Test;
use Skynet\Uint8Array;
use function Skynet\functions\mysky\generateSeedPhrase;
use function Skynet\functions\mysky\hashToChecksumWords;
use function Skynet\functions\mysky\phraseToSeed;
use function Skynet\functions\mysky\sanitizePath;
use function Skynet\functions\mysky\sanitizePhrase;
use function Skynet\functions\mysky\seedToPhrase;
use function Skynet\functions\mysky\seedWordsToSeed;
use function Skynet\functions\mysky\validatePhrase;

/**
 * @group functions
 */
class myskyTest extends Test {
	private array $validDictionarySeeds = [
		// Typical phrase.
		"vector items adopt agenda ticket nagged devoid onward geyser mime eleven frown apart origin woes",
		// Single word repeated.
		" abbey    abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey amidst punch   ",
		"yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks eggs voyage topic  ",
	];

	private array $validSeeds = [
		// Typical phrase.
		"vector items adopt agenda ticket nagged devoid onward geyser mime eleven frown apart origin woes",
		// Single word repeated.
		" abbey    abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey amidst punch   ",
		"yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks yanks eggs voyage topic  ",
		// Words not in dictionary but prefixes are valid.
		"abb about yanked yah unctuous spry mayflower malodious jabba irish gazebo bombastic eggplant acer avoidance",
	];

	private array $invalidSeeds = [
		// 14 words
		[
			"abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey pastry abbey",
			"Phrase must be '15' words long, was '14'",
		],
		// 16 words
		[
			"abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey",
			"Phrase must be '15' words long, was '16'",
		],
		// Word is too short
		[ "ab ab ab ab ab ab ab ab ab ab ab ab ab ab ab ", "Word 1 is not at least 3 letters long" ],
		// Unrecognized prefix
		[
			"abzey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey abbey",
			'Unrecognized prefix "abz" at word 1, not found in dictionary',
		],
		// 13th word falls outside first 256 words
		[
			"eggs abbey eggs abbey eggs abbey eggs abbey eggs abbey eggs abbey eight abbey eggs",
			"Prefix for word 13 must be found in the first 256 words of the dictionary",
		],
	];


	public function testGenerateSeedPhrase_GeneratedPhraseXShouldBeAValidPhrase() {
		$phrases = [];

		for ( $i = 0; $i < 100; $i ++ ) {
			$phrases[] = generateSeedPhrase();
		}

		foreach ( $phrases as $phrase ) {
			[ $valid ] = validatePhrase( $phrase );
			expect( $valid )->toBeTrue();
		}
	}

	public function testValidatePhrase_ValidatephraseShouldReturnTrueForPhraseX() {
		foreach ( $this->validSeeds as $seed ) {
			[ $valid, $error ] = validatePhrase( $seed );
			expect( $error )->toBeEmpty();
			expect( $valid )->toBeTrue();
		}
	}

	public function testValidatePhrase_ValidatephraseShouldReturnFalseForPhrase() {
		foreach ( $this->invalidSeeds as $seed ) {
			[ $valid, $error ] = validatePhrase( $seed[0] );
			expect( $error )->toEqual( $seed[1] );
			expect( $valid )->toBeFalse();
		}
	}

	public function testHashToChecksumWords_ShouldConvertCompletelyFilledHashBytesToChecksumWords() {
		$hashBytes     = Uint8Array::from( array_fill( 0, 64, 0xff ) );
		$checksumWords = hashToChecksumWords( $hashBytes );

		expect( $checksumWords[0] )->toEqual( 1023 );
		expect( $checksumWords[1] )->toEqual( 1023 );
	}

	public function testHashToChecksumWords_ShouldConvertCustomBytesToChecksumWords() {
		$hashBytes     = Uint8Array::from( [ 0b01011100, 0b00110011, 0b01010101 ] );
		$checksumWords = hashToChecksumWords( $hashBytes );
		expect( $checksumWords[0] )->toEqual( 0b0101110000 );
		expect( $checksumWords[1] )->toEqual( 0b1100110101 );
	}

	public function testPhrasetoseedseedtophrase_PhrasetoseedShouldConvertValidDictionaryPhraseSAndSeedtophraseShouldConvertItBackToTheOriginalPhrase() {
		foreach ( $this->validDictionarySeeds as $phrase ) {
			$seed           = phraseToSeed( $phrase );
			$returnedPhrase = seedToPhrase( $seed );
			expect( $returnedPhrase )->toEqual( sanitizePhrase( $phrase ) );
		}
	}

	public function testSeedWordsToSeed_ShouldConvertCustomSeedWordsToAnArrayOfSeedBytes(){
		$seedWords = Uint8Array::from( [
			0b0101110001,
			0b1000110011,
			0b1001010101,
			0b0101110010,
			0b0100010100,
			0b1101111111,
			0b0000000001,
			0b1111111110,
			0b0001111000,
			0b1111000001,
			0b0111001100,
			0b0110100111,
			0b11100101,
		]);

		$seed = seedWordsToSeed( $seedWords);
		expect($seed[0])->toEqual(0b01011100);
		expect($seed[1])->toEqual(0b01100011);
		expect($seed[2])->toEqual(0b00111001);
		expect($seed[3])->toEqual(0b01010101);
		expect($seed[4])->toEqual(0b01110010);
		expect($seed[5])->toEqual(0b01000101);
		expect($seed[6])->toEqual(0b00110111);
		expect($seed[7])->toEqual(0b11110000);

		expect($seed[8])->toEqual(0b00000111);
		expect($seed[9])->toEqual(0b11111110);
		expect($seed[10])->toEqual(0b00011110);
		expect($seed[11])->toEqual(0b00111100);
		expect($seed[12])->toEqual(0b00010111);
		expect($seed[13])->toEqual(0b00110001);
		expect($seed[14])->toEqual(0b10100111);
		expect($seed[15])->toEqual(0b11100101);

	}
}
