<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class GetFileContentResponse extends Entity {
	/**
	 * @var
	 */
	protected $data;
	/**
	 * @var string
	 */
	protected string $contentType;
	/**
	 * @var string
	 */
	protected string $portalUrl;
	/**
	 * @var string
	 */
	protected string $skylink;

	/**
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @return string
	 */
	public function getContentType(): string {
		return $this->contentType;
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
