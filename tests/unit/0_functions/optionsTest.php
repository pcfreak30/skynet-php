<?php


use Codeception\MockeryModule\Test;
use Codeception\Verify\Verify;
use Skynet\Entity;
use function Skynet\functions\options\extractOptions;
/**
 * @group functions
 */
class optionsTest extends Test {
	public function testExtractOptions_ShouldReturnEmptyIfNoOptionsMatchTheDefaults() {
		$model = [ 'foo' => 1, 'bar' => 2 ];

		expect( extractOptions( new Entity(), $model ))->toBeEmpty();
	}
}
