<?php


use _support\BaseTest;
use Codeception\Verify\Verify;
use function Skynet\functions\strings\hexToUint8Array;
use function Skynet\functions\strings\trimPrefix;
use function Skynet\functions\strings\trimSuffix;
use function Skynet\functions\url\extractDomainForPortal;
use function Skynet\functions\url\getFullDomainUrlForPortal;
use function Skynet\functions\url\makeUrl;
/**
 * @group functions
 */
class urlTest extends BaseTest {
	private string $skylinkBase32 = 'bg06v2tidkir84hg0s1s4t97jaeoaa1jse1svrad657u070c9calq4g';

	public function testGetFullDomainUrlForPortal_DomainXShouldReturnCorrectlyFormedFullUrlX() {
		$domains = [];
		$path    = '/path/File.json';

		$expectedUrl = 'https://dac.hns.siasky.net';
		$hnsDomains  = combineStrings( [ '', 'sia:', 'sia://', 'SIA:', 'SIA://' ], [ 'dac.hns', 'DAC.HNS' ], [
			'',
			'/',
		] );
		$this->addTestCases( $domains, $hnsDomains, $expectedUrl );

		$expectedPathUrl = $expectedUrl . $path;
		$hnsPathDomains  = combineStrings( $hnsDomains, [ $path ] );
		$this->addTestCases( $domains, $hnsPathDomains, $expectedPathUrl );

		$expectedSkylinkUrl = "https://{$this->skylinkBase32}.siasky.net";
		$skylinkDomains     = combineStrings( [ '', 'sia:', 'sia://' ], [ $this->skylinkBase32 ], [ '', '/' ] );
		$this->addTestCases( $domains, $skylinkDomains, $expectedSkylinkUrl );

		$expectedSkylinkPathUrl = $expectedSkylinkUrl . $path;
		$skylinkPathDomains     = combineStrings( $skylinkDomains, [ $path ] );
		$this->addTestCases( $domains, $skylinkPathDomains, $expectedSkylinkPathUrl );

		$expectedLocalhostUrl = 'localhost';
		$localhostDomains     = combineStrings( [ '', 'sia:', 'sia://' ], [ 'localhost' ], [ '', '/' ] );
		$this->addTestCases( $domains, $localhostDomains, $expectedLocalhostUrl );

		$expectedLocalhostPathUrl = $expectedLocalhostUrl . $path;
		$localhostPathDomains     = combineStrings( $localhostDomains, [ $path ] );
		$this->addTestCases( $domains, $localhostPathDomains, $expectedLocalhostPathUrl );

		foreach ( $domains as $domain ) {
			$url = getFullDomainUrlForPortal( $this->portalUrl, $domain[0] );
			expect( $url )->toEqual( $domain[1] );
		}
	}

	private function addTestCases( array &$cases, array $inputs, string $expected ) {
		$mappedInputs = array_map( function ( $input ) use ( $expected ) {
			return [ $input, $expected ];
		}, $inputs );

		array_push( $cases, ...$mappedInputs );
	}

	public function testExtractDomainForPortal_ShouldExtractFromFullUrlXTheDomainX() {
		$urls = [];
		$path = '/path/File.json';

		$expectedDomain = 'dac.hns';
		$hnsUrls        = combineStrings( [ '', 'https://', 'HTTPS://' ], [
			'dac.hns.siasky.net',
			'DAC.HNS.SIASKY.NET',
		], [ '', '/' ] );
		$this->addTestCases( $urls, $hnsUrls, $expectedDomain );

		$expectedPathDomain = $expectedDomain . $path;
		$hnsPathUrls        = combineStrings( $hnsUrls, [ $path ] );
		$this->addTestCases( $urls, $hnsPathUrls, $expectedPathDomain );

		$expectedSkylinkDomain = $this->skylinkBase32;
		$skylinkUrls           = combineStrings( [ "", "https://" ], [ "{$this->skylinkBase32}.siasky.net" ], [
			"",
			"/",
		] );
		$this->addTestCases( $urls, $skylinkUrls, $expectedSkylinkDomain );

		$expectedSkylinkPathDomain = $expectedSkylinkDomain . $path;
		$skylinkPathUrls           = combineStrings( $skylinkUrls, [ $path ] );
		$this->addTestCases( $urls, $skylinkPathUrls, $expectedSkylinkPathDomain );

		$expectedLocalhostDomain = 'localhost';
		$localhostUrls           = combineStrings( [ '', 'https://' ], [ 'localhost' ], [ '', '/' ] );
		$this->addTestCases( $urls, $localhostUrls, $expectedLocalhostDomain );

		$expectedLocalhostPathDomain = $expectedLocalhostDomain . $path;
		$localhostPathUrls           = combineStrings( $localhostUrls, [ $path ] );
		$this->addTestCases( $urls, $localhostPathUrls, $expectedLocalhostPathDomain );

		$expectedTraditionalUrlDomain = 'traditionalurl.com';
		$traditionalUrls              = combineStrings( [ '', 'https://' ], [ 'traditionalUrl.com' ], [ '', '/' ] );
		$this->addTestCases( $urls, $traditionalUrls, $expectedTraditionalUrlDomain );

		$expectedTraditionalUrlPathDomain = $expectedTraditionalUrlDomain . $path;
		$traditionalPathUrls              = combineStrings( $traditionalUrls, [ $path ] );
		$this->addTestCases( $urls, $traditionalPathUrls, $expectedTraditionalUrlPathDomain );

		$expectedTraditionalUrlSubdomain = 'subdomain.traditionalurl.com';
		$traditionalSubdomainUrls        = combineStrings( [ '', 'https://' ], [ 'subdomain.traditionalUrl.com' ], [
			'',
			'/',
		] );
		$this->addTestCases( $urls, $traditionalSubdomainUrls, $expectedTraditionalUrlSubdomain );

		foreach ( $urls as $index => $url ) {
			$receivedDomain = extractDomainForPortal( $this->portalUrl, $url[0] );
			expect( $receivedDomain )->toEqual( $url[1] );
		}
	}

	public function testMakeUrl_ShouldReturnCorrectlyFormedUrls() {
		expect( makeUrl( $this->portalUrl, "/" ) )->toEqual( "{$this->portalUrl}/" );
		expect( makeUrl( $this->portalUrl, "/skynet" ) )->toEqual( "{$this->portalUrl}/skynet" );
		expect( makeUrl( $this->portalUrl, "/skynet/" ) )->toEqual( "{$this->portalUrl}/skynet/" );

		expect( makeUrl( $this->portalUrl, "/", $this->skylink ) )->toEqual( "{$this->portalUrl}/{$this->skylink}" );
		expect( makeUrl( $this->portalUrl, "/skynet", $this->skylink ) )->toEqual( "{$this->portalUrl}/skynet/{$this->skylink}" );
		expect( makeUrl( $this->portalUrl, "//skynet/", $this->skylink ) )->toEqual( "{$this->portalUrl}/skynet/{$this->skylink}" );
		expect( makeUrl( $this->portalUrl, "/skynet/", "{$this->skylink}?foo=bar" ) )->toEqual( "{$this->portalUrl}/skynet/{$this->skylink}?foo=bar" );
		expect( makeUrl( $this->portalUrl, "{$this->skylink}/?foo=bar" ) )->toEqual( "{$this->portalUrl}/{$this->skylink}?foo=bar" );
		expect( makeUrl( $this->portalUrl, "{$this->skylink}#foobar" ) )->toEqual( "{$this->portalUrl}/{$this->skylink}#foobar" );
	}

	public function testMakeUrl_ShouldThrowIfNoArgsProvided() {
		Verify::Callable( function () {
			makeUrl();
		} )->throws( Exception::class, "Expected 'parameter', 'args', to be non-empty, was type 'array', value array (
)" );
	}

	public function testTrimPrefix_ShouldTrimThePrefixWithLimitIfPassed() {
		expect( trimPrefix( '//asdf', '/', 1 ) )->toEqual( '/asdf' );
		expect( trimPrefix( '//asdf', '/', 0 ) )->toEqual( '//asdf' );
	}

	public function testTrimSuffix_ShouldTrimTheSuffixWithLimitIfPassed() {
		expect( trimSuffix( 'asdf//', '/', 1 ) )->toEqual( 'asdf/' );
		expect( trimSuffix( 'asdf//', '/', 0 ) )->toEqual( 'asdf//' );
	}

	protected function _before() {
		parent::_before(); // TODO: Change the autogenerated stub
	}
}
