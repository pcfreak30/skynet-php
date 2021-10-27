<?php

namespace Skynet;

use Skynet\Uint8Array;
use function Skynet\functions\encoding\encodeSkylinkBase64;
use function Skynet\functions\sia\decodeSkylink;
use function Skynet\functions\validation\validateUint8ArrayLen;
use const Skynet\RAW_SKYLINK_SIZE;

/**
 *
 */
class SiaSkylink {
	/**
	 * @var int
	 */
	private int $bitfield;
	/**
	 * @var \Skynet\Uint8Array
	 */
	private Uint8Array $merkleRoot;

	/**
	 * @param int                $bitfield
	 * @param \Skynet\Uint8Array $merkleRoot
	 */
	public function __construct( int $bitfield, Uint8Array $merkleRoot ) {
		$this->bitfield   = $bitfield;
		$this->merkleRoot = $merkleRoot;
	}

	/**
	 * @param string $skylink
	 *
	 * @return static
	 * @throws \Exception
	 */
	public static function fromString( string $skylink ): self {
		$bytes = decodeSkylink( $skylink );

		return self::fromBytes( $bytes );
	}

	/**
	 * @param \Skynet\Uint8Array $data
	 *
	 * @return \Skynet\SiaSkylink
	 * @throws \Exception
	 */
	public static function fromBytes( \Skynet\Uint8Array $data ) {
		validateUint8ArrayLen( "data", $data, "parameter", RAW_SKYLINK_SIZE );

		$bitfield = $data[0];

		$merkleRoot = new Uint8Array( 32 );
		$merkleRoot->set( array_slice( $data->getData(), 2 ) );

		return new self( $bitfield, $merkleRoot );
	}

	/**
	 * @return \Skynet\Uint8Array
	 */
	public function getMerkleRoot(): Uint8Array {
		return $this->merkleRoot;
	}

	/**
	 * @param \Skynet\Uint8Array $merkleRoot
	 */
	public function setMerkleRoot( Uint8Array $merkleRoot ): void {
		$this->merkleRoot = $merkleRoot;
	}

	/**
	 * @return string
	 */
	public function toString(): string {
		return $this->__toString();
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		return encodeSkylinkBase64( $this->toBytes() );
	}

	/**
	 * @return \Skynet\Uint8Array
	 * @throws \Exception
	 */
	public function toBytes(): Uint8Array {
		$bytes = new Uint8Array( RAW_SKYLINK_SIZE );
		$bytes->set( $this->bitfield );
		$bytes->set( $this->merkleRoot, 2 );

		return $bytes;
	}

}
