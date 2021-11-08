<?php

namespace Skynet\Types;

use function Skynet\functions\mysky\genKeyPairFromSeed;
use function Skynet\functions\crypto\genKeyPairFromSeed as genKeyPairFromPlainSeed;

/**
 *
 */
class KeyPairAndSeed extends KeyPair {
	/**
	 * @var string
	 */
	protected string $seed;

	/**
	 * @param string $seed
	 *
	 * @return static
	 */
	public static function fromSeed( string $seed ): self {
		return new self( array_merge( genKeyPairFromSeed( $seed )->toArray(), [ 'seed' => $seed ] ) );
	}

	public static function fromPlainSeed( string $seed ): self {
		return new self( array_merge( genKeyPairFromPlainSeed( $seed )->toArray(), [ 'seed' => $seed ] ) );
	}


	/**
	 * @return string
	 */
	public function getSeed(): string {
		return $this->seed;
	}

	/**
	 * @param string $seed
	 */
	public function setSeed( string $seed ): void {
		$this->seed = $seed;
	}
}
