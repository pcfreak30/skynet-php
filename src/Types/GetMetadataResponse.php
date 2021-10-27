<?php

namespace Skynet\Types;

use Skynet\Entity;
use stdClass;

/**
 *
 */
class GetMetadataResponse extends Entity {
	/**
	 * @var \stdClass
	 */
	protected stdClass $metadata;
	/**
	 * @var string
	 */
	protected string $portalUrl;
	/**
	 * @var string
	 */
	protected string $skylink;

	/**
	 * @return array
	 */
	public function getMetadata(): stdClass {
		return $this->metadata;
	}

	/**
	 * @return string
	 */
	public function getPortalUrl(): string {
		return $this->portalUrl;
	}

	/**
	 * @return string
	 */
	public function getSkylink(): string {
		return $this->skylink;
	}


}
