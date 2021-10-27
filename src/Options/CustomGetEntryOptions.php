<?php

namespace Skynet\Options;

/**
 *
 */
class CustomGetEntryOptions extends CustomClientOptions {
	/**
	 * @var string
	 */
	protected string $endpointGetEntry;
	/**
	 * @var bool
	 */
	protected bool $hashedDataKeyHex;

	/**
	 * @return string
	 */
	public function getEndpointGetEntry(): string {
		return $this->endpointGetEntry;
	}

	/**
	 * @return bool
	 */
	public function isHashedDataKeyHex(): bool {
		return $this->hashedDataKeyHex;
	}

	/**
	 * @param bool $hashedDataKeyHex
	 */
	public function setHashedDataKeyHex( bool $hashedDataKeyHex ): void {
		$this->hashedDataKeyHex = $hashedDataKeyHex;
	}
}
