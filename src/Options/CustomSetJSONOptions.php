<?php

namespace Skynet\Options;

use stdClass;

/**
 *
 */
class CustomSetJSONOptions extends CustomSetEntryOptions {
	/**
	 * @var string|null
	 */
	protected ?string $cachedDownloadLink = null;
	/**
	 * @var string|null
	 */
	protected ?string $cachedDataLink = null;
	/**
	 * @var string|null
	 */
	protected ?string $endpointUpload = null;
	/**
	 * @var string|null
	 */
	protected ?string $endpointLargeUpload = null;
	/**
	 * @var string|null
	 */
	protected ?string $customFilename = null;
	/**
	 * @var array|null
	 */
	protected ?array $errorPages = null;
	/**
	 * @var int|null
	 */
	protected ?int $largeFileSize = null;
	/**
	 * @var array|null
	 */
	protected ?array $retryDelays = null;
	/**
	 * @var array|null
	 */
	protected ?array $tryFiles = null;

	/**
	 * @return string|null
	 */
	public function getCachedDataLink(): ?string {
		return $this->cachedDataLink;
	}

	/**
	 * @return string|null
	 */
	public function getCachedDownloadLink(): ?string {
		return $this->cachedDownloadLink;
	}
}
