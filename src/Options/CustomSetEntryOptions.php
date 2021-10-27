<?php

namespace Skynet\Options;

use Skynet\Options\CustomClientOptions;

/**
 *
 */
class CustomSetEntryOptions extends CustomClientOptions {
	/**
	 * @var string|null
	 */
	protected ?string $endpointSetEntry = null;
	/**
	 * @var bool
	 */
	protected bool $hashedDataKeyHex = false;

	/**
	 * @return string|null
	 */
	public function getEndpointSetEntry(): ?string {
		return $this->endpointSetEntry;
	}

	/**
	 * @return bool|null
	 */
	public function getHashedDataKeyHex(): bool {
		return $this->hashedDataKeyHex;
	}

	/**
	 * @param bool|null $hashedDataKeyHex
	 */
	public function setHashedDataKeyHex( bool $hashedDataKeyHex ): void {
		$this->hashedDataKeyHex = $hashedDataKeyHex;
	}
}
