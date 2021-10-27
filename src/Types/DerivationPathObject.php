<?php

namespace Skynet\Types;

use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class DerivationPathObject extends Entity {
	/**
	 * @var \Skynet\Uint8Array
	 */
	protected Uint8Array $pathSeed;
	/**
	 * @var bool
	 */
	protected bool $directory;

	/**
	 * @return \Skynet\Uint8Array
	 */
	public function getPathSeed(): Uint8Array {
		return $this->pathSeed;
	}

	/**
	 * @return bool
	 */
	public function isDirectory(): bool {
		return $this->directory;
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @var string
	 */
	protected string $name;
}
