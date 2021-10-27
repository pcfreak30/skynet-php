<?php

namespace Skynet\Types;

use Skynet\Entity;
use stdClass;

/**
 *
 */
class ResolveHnsResponse extends Entity {
	/**
	 * @var \stdClass
	 */
	protected stdClass $data;
	/**
	 * @var string
	 */
	protected string $skylink;

	/**
	 * @return \stdClass
	 */
	public function getData(): stdClass {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function getSkylink(): string {
		return $this->skylink;
	}

}
