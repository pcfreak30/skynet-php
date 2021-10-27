<?php

namespace Skynet;


use function Skynet\functions\encoding\encodePrefixedBytes;

/**
 *
 */
class SiaPublicKey {
	/**
	 * @var \Skynet\Uint8Array
	 */
	private Uint8Array $algorithm;
	/**
	 * @var \Skynet\Uint8Array
	 */
	private Uint8Array $key;

	/**
	 * @param \Skynet\Uint8Array $algorithm
	 * @param \Skynet\Uint8Array $key
	 */
	public function __construct( Uint8Array $algorithm, Uint8Array $key ) {
		$this->algorithm = $algorithm;
		$this->key       = $key;
	}

	/**
	 * @return \Skynet\Uint8Array
	 * @throws \Exception
	 */
	public function marshalSia(): Uint8Array {
		$bytes = new Uint8Array( SPECIFIER_LEN + 8 + PUBLIC_KEY_SIZE );
		$bytes->set( $this->algorithm );
		$bytes->set( encodePrefixedBytes( $this->key ), SPECIFIER_LEN );

		return $bytes;
	}
}
