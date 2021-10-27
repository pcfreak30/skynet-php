<?php

namespace Skynet\Options;

/**
 *
 */
class CustomGetMetadataOptions extends CustomClientOptions {
	/**
	 * @var string
	 */
	protected string $endpointGetMetadata;

	/**
	 * @return string
	 */
	public function getEndpointGetMetadata(): string {
		return $this->endpointGetMetadata;
	}
}
