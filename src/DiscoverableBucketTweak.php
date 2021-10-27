<?php

namespace Skynet;

use function Skynet\functions\crypto\hashAll;

/**
 *
 */
class DiscoverableBucketTweak {
	/**
	 * @var int
	 */
	private int $version;
	/**
	 * @var array
	 */
	private array $path;

	/**
	 * @param string $path
	 */
	public function __construct( string $path ) {
		$paths         = explode( '/', $path );
		$paths         = array_filter( $paths );
		$pathHashes    = array_map( '\Skynet\functions\tweak\hashPathComponent', $paths );
		$this->version = DISCOVERABLE_BUCKET_TWEAK_VERSION;
		$this->path    = $pathHashes;
	}

	/**
	 * @return \Skynet\Uint8Array
	 */
	public function getHash() {
		$encoding = $this->encode();

		return hashAll( $encoding );
	}

	/**
	 * @return \Skynet\Uint8Array
	 * @throws \Exception
	 */
	public function encode(): Uint8Array {
		$size   = 1 + ( 32 * count( $this->path ) );
		$buffer = new Uint8Array( $size );
		$buffer->set( $this->version );

		$offset = 1;

		foreach ( $this->path as $pathLevel ) {
			$buffer->set( $pathLevel, $offset );
			$offset += 32;
		}

		return $buffer;
	}
}
