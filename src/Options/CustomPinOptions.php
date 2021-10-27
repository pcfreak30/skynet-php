<?php

namespace Skynet\Options;

/**
 *
 */
class CustomPinOptions extends CustomClientOptions {
	/**
	 * @var string
	 */
	protected string $endpointPin;

	/**
	 * @return string
	 */
	public function getEndpointPin(): string {
		return $this->endpointPin;
	}
}
