<?php


namespace Skynet;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Exception;
use IteratorAggregate;

/**
 *
 */
class Uint8Array implements ArrayAccess, IteratorAggregate, Countable {
	/**
	 * @var array
	 */
	private array $data = [];
	/**
	 * @var int
	 */
	private int $maxLength = 0;
	/**
	 * @var bool
	 */
	private bool $allowInt = false;
	/**
	 * @var bool
	 */
	private bool $littleIndian = false;

	/**
	 * @param int $length
	 */
	public function __construct( int $length = 0 ) {
		if ( $length > 0 ) {
			$this->data      = array_fill( 0, $length, 0 );
			$this->maxLength = $length;
		}
	}

	/**
	 * @param $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->data[ $offset ] );
	}

	/**
	 * @param $offset
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->data[ $offset ];
	}

	/**
	 * @param $offset
	 * @param $value
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		$this->data[ $offset ] = $value;
	}

	/**
	 * @param $offset
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		unset( $this->data[ $offset ] );
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->data );
	}

	/**
	 * @return int
	 */
	public function count() {
		return count( $this->data );
	}

	/**
	 * @return bool
	 */
	public function isLittleIndian(): bool {
		return $this->littleIndian;
	}

	/**
	 * @param bool $littleIndian
	 */
	public function setLittleIndian( bool $littleIndian ): void {
		$this->littleIndian = $littleIndian;
	}

	/**
	 * @return string
	 * @throws \SodiumException
	 */
	public function toHex() {
		return \Sodium\bin2hex( $this->toString() );
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
		$data = array_map( [ $this, 'packByte' ], $this->data );

		return implode( '', $data );
	}

	/**
	 * @param \Skynet\Uint8Array $array
	 *
	 * @return bool
	 */
	public function compare( Uint8Array $array ): bool {
		return $array->getData() === $this->data;
	}

	/**
	 * @return array
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * @param int      $offset
	 * @param int|null $length
	 *
	 * @return $this
	 * @throws \Exception
	 */
	public function slice( int $offset, int $length = null ): self {
		if ( $offset + $length > $this->getMaxLength() ) {
			throw new Exception( 'Combined offset and length are greater than array size.' );
		}

		return Uint8Array::from( array_slice( $this->data, $offset, $length ) );
	}

	/**
	 * @return int
	 */
	public function getMaxLength(): int {
		return $this->maxLength;
	}

	/**
	 * @param int $maxLength
	 */
	public function setMaxLength( int $maxLength ): void {
		$this->maxLength = $maxLength;
	}

	/**
	 * @param      $data
	 * @param int  $length
	 * @param null $littleEndian
	 *
	 * @return static
	 * @throws \Exception
	 */
	public static function from( $data, $length = 0, $littleEndian = null ): self {
		if ( is_string( $data ) ) {
			if ( strlen( $data ) > 0 ) {
				if ( preg_match( '@(?:[A-Za-z0-9+/]{4})*(?:[A-Za-z0-9+/]{2}==|[A-Za-z0-9+/]{3}=)@', $data ) ) {
					$testData = base64_decode( $data );
					if ( base64_encode( $testData ) === $data ) {
						$data = $testData;
					}
				}
				$data = str_split( $data, $littleEndian ? 2 : 1 );
			} else {
				$data = [];
			}

			return self::from( $data, $length ?? count( $data ), $littleEndian );
		}
		if ( ! is_array( $data ) && ! ( $data instanceof ArrayAccess ) ) {
			throw new Exception( '$data must either be an array or an array-like object with ArrayAccess.' );
		}

		$inst = new self( $length );
		$inst->set( $data, 0, $littleEndian );

		return $inst;
	}

	/**
	 * @param      $data
	 * @param int  $offset
	 * @param null $littleEndian
	 *
	 * @return void
	 * @throws \Exception
	 */
	public function set( $data, $offset = 0, $littleEndian = null ): void {

		if ( $data instanceof self ) {
			$data = $data->getData();
		} else {
			if ( is_string( $data ) ) {
				$data = str_split( $data, $this->littleIndian ? 2 : 1 );
			}

			$data = (array) $data;

			if ( null !== $littleEndian ) {
				$endian             = $this->littleIndian;
				$this->littleIndian = $littleEndian;
			}

			if ( 0 < count( $data ) ) {
				$data = array_map( [ $this, 'unpackByte' ], $data );
			}

			if ( isset( $endian ) ) {
				$this->littleIndian = $endian;
			}
		}

		$dataLen   = count( $data );
		$offsetLen = $dataLen + $offset;

		if ( 0 === $this->maxLength ) {
			$this->maxLength = count( $data );
			$this->data      = $data;
		} elseif ( $offsetLen === $this->maxLength && 0 === $offset ) {
			$this->data = $data;
		} elseif ( ( $offsetLen <= $this->maxLength && 0 < $offset ) || ( $offsetLen < $this->maxLength ) ) {
			for ( $i = $offset; $i < $offsetLen; $i ++ ) {
				$this->data[ $i ] = $data[ $i - $offset ];
			}
		} else {
			throw new Exception( 'Invalid data' );
		}
	}

	/**
	 * @param $byte
	 *
	 * @return int|mixed|null
	 */
	private function unpackByte( $byte ) {
		if ( is_int( $byte ) && ! $this->allowInt ) {
			return $byte;
		}

		$mode = 'C';

		if ( $this->littleIndian ) {
			$mode = 'v';
		}

		$int = unpack( $mode, $byte );

		return array_pop( $int );
	}

	/**
	 * @param $byte
	 *
	 * @return false|string
	 */
	private function packByte( $byte ) {
		return pack( 'C', $byte );
	}
}
