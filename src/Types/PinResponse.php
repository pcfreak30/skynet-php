<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class PinResponse extends Entity {
	/**
	 * @var string
	 */
	protected string $skylink;

	/**
	 * @return string
	 */
	public function getSkylink(): string {
		return $this->skylink;
	}
}
