<?php

namespace Skynet\Types;

use Skynet\Entity;

/**
 *
 */
class KeyPair extends Entity {
	/**
	 * @var string
	 */
	protected string $publicKey;
	/**
	 * @var string
	 */
	protected string $privateKey;

	/**
	 * @return string
	 */
	public function getPublicKey(): string {
		return $this->publicKey;
	}

	/**
	 * @param string $publicKey
	 */
	public function setPublicKey( string $publicKey ): void {
		$this->publicKey = $publicKey;
	}

	/**
	 * @return string
	 */
	public function getPrivateKey(): string {
		return $this->privateKey;
	}

	/**
	 * @param string $privateKey
	 */
	public function setPrivateKey( string $privateKey ): void {
		$this->privateKey = $privateKey;
	}

}
