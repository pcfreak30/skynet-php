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

	protected ?string $dataLink = null;

	/**
	 * @return string|null
	 */
	public function getDataLink(): ?string {
		return $this->dataLink;
	}

	/**
	 * @param string|null $dataLink
	 */
	public function setDataLink( ?string $dataLink ): void {
		$this->dataLink = $dataLink;
	}

	/**
	 * @return array|null
	 */
	public function getData(): ?\stdClass {
		return $this->data;
	}
}
