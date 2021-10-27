<?php

namespace Skynet\Options;

/**
 *
 */
class CustomDownloadOptions extends CustomClientOptions {
	/**
	 * @var string
	 */
	protected string $endpointDownload;
	/**
	 * @var string
	 */
	protected bool $download;
	/**
	 * @var string
	 */
	protected string $path;
	/**
	 * @var string
	 */
	protected string $responseType;
	/**
	 * @var bool
	 */
	protected bool $subdomain;

	/**
	 * @var string
	 */
	protected string $range;

	/**
	 * @return string
	 */
	public function getEndpointDownload(): string {
		return $this->endpointDownload;
	}

	/**
	 * @return string
	 */
	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return string
	 */
	public function getResponseType(): string {
		return $this->responseType;
	}

	/**
	 * @return bool
	 */
	public function isSubdomain(): bool {
		return $this->subdomain;
	}

	/**
	 * @return string
	 */
	public function getRange(): string {
		return $this->range;
	}

	/**
	 * @param string $range
	 */
	public function setRange( string $range ): void {
		$this->range = $range;
	}

	/**
	 * @return string
	 */
	public function getDownload() {
		return $this->download;
	}
}
