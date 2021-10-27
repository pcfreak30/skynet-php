<?php


use _support\BaseTest;
use function Skynet\functions\formatting\convertSkylinkToBase32;
use function Skynet\functions\formatting\convertSkylinkToBase64;
use function Skynet\functions\formatting\formatSkylink;
use const Skynet\URI_SKYNET_PREFIX;

/**
 * @group functions
 */
class formattingTest extends BaseTest {

	private string $skylinkBase64 = 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';
	private string $skylinkBase32 = 'bg06v2tidkir84hg0s1s4t97jaeoaa1jse1svrad657u070c9calq4g';

	public function testConvertSkylinkToBase32_ShouldConvertTheBase64SkylinkToBase32() {
		$encoded = convertSkylinkToBase32( $this->skylinkBase64 );

		expect( $encoded )->toEqual( $this->skylinkBase32 );
	}

	public function testConvertSkylinkToBase64_ShouldConvertTheBase64SkylinkToBase32() {
		$encoded = convertSkylinkToBase64( $this->skylinkBase32 );

		expect( $encoded )->toEqual( $this->skylinkBase64 );
	}

	public function testFormatSkylink_ShouldEnsureTheSkylinkStartsWithThePrefix() {
		$prefixedSkylink = URI_SKYNET_PREFIX . $this->skylinkBase64;

		expect( formatSkylink( $this->skylinkBase64 ) )->toEqual( $prefixedSkylink );
		expect( formatSkylink( $prefixedSkylink ) )->toEqual( $prefixedSkylink );
	}

	public function testFormatSkylink_ShouldNotPrependAPrefixForTheEmptyString() {
		expect( formatSkylink( '' ) )->toEqual( '' );
	}
}
