<?php

namespace Skynet\Options;

use stdClass;

/**
 *
 */
class CustomUploadOptions extends CustomSetEntryOptions {
	/**
	 * @var string|null
	 */
	protected ?string $endpointUpload;
	/**
	 * @var string|null
	 */
	protected ?string $endpointLargeUpload;
	/**
	 * @var string|null
	 */
	protected ?string $customFilename;
	/**
	 * @var array|null
	 */
	protected ?array $errorPages = null;
	/**
	 * @var int|null
	 */
	protected ?int $largeFileSize;
	/**
	 * @var array|null
	 */
	protected ?array $retryDelays;
	/**
	 * @var array|null
	 */
	protected ?array $tryFiles = null;

	/**
	 * @return string|null
	 */
	public function getEndpointUpload(): ?string {
		return $this->endpointUpload;
	}

	/**
	 * @return string|null
	 */
	public function getEndpointLargeUpload(): ?string {
		return $this->endpointLargeUpload;
	}

	/**
	 * @return string|null
	 */
	public function getCustomFilename(): ?string {
		return $this->customFilename;
	}

	/**
	 * @return \stdClass|null
	 */
	public function getErrorPages(): ?array {
		return $this->errorPages;
	}

	/**
	 * @return int|null
	 */
	public function getLargeFileSize(): ?int {
		return $this->largeFileSize;
	}

	/**
	 * @return array|null
	 */
	public function getRetryDelays(): ?array {
		return $this->retryDelays;
	}

	/**
	 * @return string|null
	 */
	public function getTryFiles(): ?array {
		return $this->tryFiles;
	}
}
