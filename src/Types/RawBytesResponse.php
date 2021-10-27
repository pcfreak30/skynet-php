<?php

namespace Skynet\Types;

use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class RawBytesResponse extends Entity {
	/**
	 * @var \Skynet\Uint8Array|null
	 */
	protected ?Uint8Array $data = null;
	/**
	 * @var string|null
	 */
	protected ?string $dataLink = null;

	/**
	 * @return \Skynet\Uint8Array|null
	 */
	public function getData(): ?Uint8Array {
		return $this->data;
	}

	/**
	 * @return string|null
	 */
	public function getDataLink(): ?string {
		return $this->dataLink;
	}
}
