<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class EncryptedFileMetadata extends Entity {
	/**
	 * @var int
	 */
	protected int $version;

	/**
	 * @return int
	 */
	public function getVersion(): int {
		return $this->version;
	}
}
