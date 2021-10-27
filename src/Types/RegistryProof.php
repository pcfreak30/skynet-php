<?php

namespace Skynet\Types;

use Exception;
use Skynet\Entity;
use stdClass;

/**
 *
 */
class RegistryProof extends Entity {
	/**
	 * @var string
	 */
	protected string $data;
	/**
	 * @var string
	 */
	protected string $revision;
	/**
	 * @var string
	 */
	protected string $datakey;
	/**
	 * @var \stdClass
	 */
	protected stdClass $publickey;
	/**
	 * @var string
	 */
	protected string $signature;
	/**
	 * @var int
	 */
	protected int $type;

	/**
	 * @return string
	 */
	public function getData(): string {
		return $this->data;
	}

	/**
	 * @return int
	 */
	public function getRevision(): string {
		return $this->revision;
	}

	/**
	 * @return \stdClass
	 */
	public function getPublickey(): stdClass {
		return $this->publickey;
	}

	/**
	 * @param \stdClass $publickey
	 */
	public function setPublickey( stdClass $publickey ): void {
		if ( empty( $publickey->algorithm ) ) {
			throw new Exception( 'publickey->algorithm is missing' );
		}

		if ( empty( $publickey->key ) ) {
			throw new Exception( 'publickey->key is missing' );
		}

		$this->publickey = $publickey;
	}

	/**
	 * @return string
	 */
	public function getSignature(): string {
		return $this->signature;
	}

	/**
	 * @return int
	 */
	public function getType(): int {
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getDatakey(): string {
		return $this->datakey;
	}
}
