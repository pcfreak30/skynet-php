<?php

namespace Skynet\Types;

use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class SignedRegistryEntry extends Entity {
	/**
	 * @var \Skynet\Types\RegistryEntry|null
	 */
	protected ?RegistryEntry $entry = null;
	/**
	 * @var \Skynet\Uint8Array|null
	 */
	protected ?Uint8Array $signature = null;

	/**
	 * @return \Skynet\Types\RegistryEntry|null
	 */
	public function getEntry(): ?RegistryEntry {
		return $this->entry;
	}

	/**
	 * @return \Skynet\Uint8Array|null
	 */
	public function getSignature(): ?Uint8Array {
		return $this->signature;
	}
}
