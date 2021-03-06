<?php


use _support\BaseTest;
use BN\BN;
use Codeception\Verify\Verify;
use Skynet\Types\RegistryEntry;
use function Skynet\functions\formatting\parseSkylinkBase32;
use function Skynet\functions\options\makeParseSkylinkOptions;
use function Skynet\functions\registry\signEntry;
use function Skynet\functions\skylinks\parseSkylink;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;
/**
 * @group functions
 */
class parseTest extends BaseTest {
	protected string $skylink = 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';
	private string $skylinkBase32 = 'bg06v2tidkir84hg0s1s4t97jaeoaa1jse1svrad657u070c9calq4g';
	private array $basicCases = [];
	private array $subdomainCases = [];
	private array $invalidCases = [];

	public function testParseSkylink_ShouldExtractSkylinkAndPathFromX() {
		foreach ( $this->basicCases as $fullSkylink ) {
			$path     = extractNonSkylinkPath( $fullSkylink, $this->skylink );
			$fullPath = $this->skylink . $path;

			expect( parseSkylink( $fullPath, makeParseSkylinkOptions( [ 'includePath' => true ] ) ) )->toEqual( $fullPath );
			expect( parseSkylink( $fullPath, makeParseSkylinkOptions( [ 'onlyPath' => true ] ) ) )->toEqual( $path );
		}
	}

	public function testParseSkylinkShouldExtractBase32SkylinkFromX() {
		foreach ( $this->subdomainCases as $fullSkylink ) {
			expect( parseSkylink( $fullSkylink, makeParseSkylinkOptions( [ 'fromSubdomain' => true ] ) ) )->toEqual( $this->skylinkBase32 );
			expect( parseSkylinkBase32( $fullSkylink ) )->toEqual( $this->skylinkBase32 );

			$expectedPath = extractNonSkylinkPath( $fullSkylink, '' );
			$path         = parseSkylink( $fullSkylink, makeParseSkylinkOptions( [
				'fromSubdomain' => true,
				'onlyPath'      => true,
			] ) );
			expect( $path )->toEqual( $expectedPath );
		}
	}

	protected function _before() {
		$this->basicCases     = combineStrings(
			[
				"",
				"sia:",
				"sia://",
				"https://siasky.net/",
				"https://foo.siasky.net/",
				"https://{$this->skylinkBase32}.siasky.net/",
			],
			[ $this->skylink ],
			[ "", "/", "//", "/foo", "/foo/", "/foo/bar", "/foo/bar/" ],
			[ "", "?", "?foo=bar", "?foo=bar&bar=baz" ],
			[ "", "#", "#foo", "#foo?bar" ]
		);
		$this->subdomainCases = combineStrings(
			[ "https://" ],
			[ $this->skylinkBase32 ],
			[ ".siasky.net", ".foo.siasky.net" ],
			[ "", "/", "//", "/foo", "/foo", "/foo/", "/foo/bar", "/foo/bar/", "/{$this->skylink}" ],
			[ "", "?", "?foo=bar", "?foo=bar&bar=baz" ],
			[ "", "#", "#foo", "#foo?bar" ]
		);
		$this->invalidCases   = [ "123", "{$this->skylink}xxx", "{$this->skylink}xxx/foo", "{$this->skylink}xxx?foo" ];
		parent::_before(); // TODO: Change the autogenerated stub
	}

	public function testParseSkylink_ShouldReturnNullOnInvalidCase() {
		foreach ($this->invalidCases as $fullSkylink){
			expect( parseSkylink( $fullSkylink))->toBeNull();
		}
	}

	public function testParseSkylink_ShouldReturnNullOnInvalidBase32Subdomain(){
		$badUrl = "https://{$this->skylinkBase32}xxx.siasky.net";
		expect( parseSkylink( $badUrl ,makeParseSkylinkOptions( [ 'fromSubdomain' => true ] )))->toBeNull();
	}

	public function testParseSkylink_ShouldRejectInvalidCombinationsOfOptions(){
		Verify::Callable( function () {
			parseSkylink('test', makeParseSkylinkOptions( [ 'includePath' => true , 'onlyPath' => true] ));
		} )->throws( Exception::class, 'The includePath and onlyPath options cannot both be set' );
		Verify::Callable( function () {
			parseSkylink('test', makeParseSkylinkOptions( [ 'includePath' => true , 'fromSubdomain' => true] ));
		} )->throws( Exception::class, 'The includePath and fromSubdomain options cannot both be set' );
	}
}
