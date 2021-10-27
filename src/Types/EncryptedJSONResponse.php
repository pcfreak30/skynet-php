<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class EncryptedJSONResponse extends Entity {
	/**
	 * @var \stdClass|null
	 */
	protected ?\stdClass $data = null;

	/**
	 * @return array|null
	 */
	public function getData(): ?\stdClass {
		return $this->data;
	}
}
