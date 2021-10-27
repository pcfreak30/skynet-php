<?php


namespace unit\functions;

use _support\BaseTest;
use BN\BN;
use Codeception\Verify\Verify;
use Skynet\Registry;
use function Skynet\functions\encoding\encodeBigintAsUint64;
use function Skynet\functions\validation\assertUint64;

/**
 * @group functions
 */
class validationTest extends BaseTest {
	public function testAssertUint64_ShouldTestTheAssertuint64Function() {
		Verify::Callable( function () {
			assertUint64( new BN( 0 ) );
		} )->assertDoesNotThrow();
		Verify::Callable( function () {
			assertUint64( new BN( - 1 ) );
		} )->throws();
		Verify::Callable( function () {
			assertUint64( new BN( Registry::getMaxRevision() ) );
		} )->doesNotThrow();
		Verify::Callable( function () {
			assertUint64( new BN( Registry::getMaxRevision()->add( new BN( 1 ) ) ) );
		} )->throws();
	}
}
