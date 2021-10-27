<?php

namespace Skynet\Types;

use Skynet\Entity;
use stdClass;

/**
 *
 */
class JSONResponse extends Entity {
	/**
	 * @var \stdClass|null
	 */
	protected ?stdClass $data = null;
	/**
	 * @var string|null
	 */
	protected ?string $dataLink = null;

	/**
	 * @return \stdClass|null
	 */
	public function getData(): ?stdClass {
		return $this->data;
	}

	/**
	 * @param \stdClass|null $data
	 */
	public function setData( ?stdClass $data ): void {
		$this->data = $data;
	}

	/**
	 * @return string|null
	 */
	public function getDataLink(): ?string {
		return $this->dataLink;
	}

	/**
	 * @param string|null $dataLink
	 */
	public function setDataLink( ?string $dataLink ): void {
		$this->dataLink = $dataLink;
	}
}
