<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class UploadRequestResponse extends Entity {
	/**
	 * @var string
	 */
	protected string $skylink;

	/**
	 * @return string
	 */
	public function getSkylink(): string {
		return $this->skylink;
	}

	/**
	 * @param string $skylink
	 */
	public function setSkylink( string $skylink ): void {
		$this->skylink = $skylink;
	}

}
