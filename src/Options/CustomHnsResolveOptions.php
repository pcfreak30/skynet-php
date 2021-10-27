<?php

namespace Skynet\Options;

/**
 *
 */
class CustomHnsResolveOptions extends CustomClientOptions {
	/**
	 * @var string
	 */
	protected string $endpointResolveHns;

	/**
	 * @return string
	 */
	public function getEndpointResolveHns(): string {
		return $this->endpointResolveHns;
	}

	/**
	 * @param string $endpointResolveHns
	 */
	public function setEndpointResolveHns( string $endpointResolveHns ): void {
		$this->endpointResolveHns = $endpointResolveHns;
	}
}
