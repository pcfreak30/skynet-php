<?php

namespace Skynet\Types;

use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class EntryData extends Entity {
	/**
	 * @var \Skynet\Uint8Array|null
	 */
	protected ?Uint8Array $data = null;

	/**
	 * @return \Skynet\Uint8Array|null
	 */
	public function getData(): ?Uint8Array {
		return $this->data;
	}

	/**
	 * @param \Skynet\Uint8Array|null $data
	 */
	public function setData( ?Uint8Array $data ): void {
		$this->data = $data;
	}
}
