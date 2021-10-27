<?php

namespace Skynet\Options;

use Skynet\Entity;

/**
 *
 */
class CustomValidateRegistryProofOptions extends Entity {
	/**
	 * @var
	 */
	protected $resolverSkylink;
	/**
	 * @var
	 */
	protected $skylink;

	/**
	 * @return mixed
	 */
	public function getResolverSkylink() {
		return $this->resolverSkylink;
	}

	/**
	 * @return mixed
	 */
	public function getSkylink() {
		return $this->skylink;
	}
}
