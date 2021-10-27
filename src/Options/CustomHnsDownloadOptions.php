<?php

namespace Skynet\Options;

/**
 *
 */
class CustomHnsDownloadOptions extends CustomDownloadOptions {
	/**
	 * @var string
	 */
	protected string $endpointDownloadHns;
	/**
	 * @var string
	 */
	protected string $hnsSubdomain;

	/**
	 * @return string
	 */
	public function getHnsSubdomain(): string {
		return $this->hnsSubdomain;
	}

	/**
	 * @return string
	 */
	public function getEndpointDownloadHns(): string {
		return $this->endpointDownloadHns;
	}
}
