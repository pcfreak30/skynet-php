<?php

namespace Skynet;

use ArrayAccess;
use ArrayIterator;
use Exception;
use IteratorAggregate;
use JetBrains\PhpStorm\Internal\LanguageLevelTypeAware;
use Traversable;

/**
 *
 */
class Entity implements IteratorAggregate, ArrayAccess {
	/**
	 * @param array $data
	 */
	public function __construct( $data = [] ) {
		$this->set( $data );
	}

	/**
	 * @param $parameters
	 *
	 * @return void
	 */
	public function set( $parameters ) {
		foreach ( $parameters as $prop => $value ) {
			if ( property_exists( $this, $prop ) && null !== $value ) {
				$setter = 'set' . ucfirst( $prop );
				if ( method_exists( $this, $setter ) ) {
					$this->$setter( $value );
				} else {
					$this->{$prop} = $value;
				}
			}
		}
	}

	/**
	 * @return \ArrayIterator
	 */
	public function getIterator() {
		return new ArrayIterator( $this->toArray() );
	}

	/**
	 * @return array
	 */
	public function toArray(): array {
		$data = [];
		foreach ( ( new \ReflectionClass( $this ) )->getProperties() as $property ) {
			$prop   = $property->getName();
			$getter = 'get' . ucfirst( $prop );
			if ( isset( $this->{$prop} ) ) {
				if ( method_exists( $this, $getter ) ) {
					$data[ $prop ] = $this->{$getter}();
				} else {
					$data[ $prop ] = $this->{$prop};
				}
			}
		}

		return $data;
	}

	/**
	 * @param $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return isset( $this->{$offset} );
	}

	/**
	 * @param $offset
	 *
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		return $this->{$offset};
	}

	/**
	 * @param $offset
	 * @param $value
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ) {
		$this->set( [ $offset => $value ] );
	}

	/**
	 * @param $offset
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ) {
		$this->set( [ $offset => null ] );
	}
}
