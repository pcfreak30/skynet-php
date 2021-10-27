<?php

namespace Skynet\Options;

/**
 *
 */
class CustomGetJSONOptions extends CustomGetEntryOptions {
	/**
	 * @var string|null
	 */
	protected ?string $cachedDownloadLink;
	/**
	 * @var string|null
	 */
	protected ?string $cachedDataLink;

	/**
	 * @return string|null
	 */
	public function getCachedDownloadLink(): ?string {
		return $this->cachedDownloadLink;
	}

	/**
	 * @return string|null
	 */
	public function getCachedDataLink(): ?string {
		return $this->cachedDataLink;
	}
}
