<?php

namespace Skynet\functions\validation;

use BN\BN;
use Exception;
use Skynet\Registry;
use Skynet\Types\RegistryEntry;
use Skynet\Uint8Array;
use function Skynet\functions\skylinks\parseSkylink;
use function Skynet\functions\strings\isHexString;
use function Skynet\functions\strings\trimPrefix;
use const Skynet\ED25519_PREFIX;

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateBigInt( string $name, $value, string $valueKind ): void {
	if ( ! ( $value instanceof BN ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'bigint'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateBoolean( string $name, $value, string $valueKind ): void {
	if ( ! is_bool( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'boolean'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateNumber( string $name, $value, string $valueKind ): void {
	if ( ! is_int( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'int'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateString( string $name, $value, string $valueKind ): void {
	if ( ! is_string( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'string'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateUint8Array( string $name, $value, string $valueKind ): void {
	if ( ! ( $value instanceof Uint8Array ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'Uint8Array'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 * @param int    $len
 *
 * @return void
 * @throws \Exception
 */
function validateUint8ArrayLen( string $name, $value, string $valueKind, int $len ): void {
	validateUint8Array( $name, $value, $valueKind );
	/** @var Uint8Array $value */
	$actualLen = $value->count();
	if ( $actualLen !== $len ) {
		throwValidationError( $name, $value, $valueKind, sprintf( "type 'Uint8Array' of length %s, was length %s", $len, $actualLen ) );
	}
}


/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 * @param int    $len
 *
 * @return void
 * @throws \Exception
 */
function validateStringLen( string $name, $value, string $valueKind, int $len ): void {
	validateString( $name, $value, $valueKind );
	$actualLen = strlen( $value );
	if ( $actualLen !== $len ) {
		throwValidationError( $name, $value, $valueKind, "type 'int'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateHexString( string $name, $value, string $valueKind ): void {
	validateString( $name, $value, $valueKind );
	if ( ! isHexString( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "a hex-encoded string" );
	}
}


/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateObject( string $name, $value, string $valueKind ): void {
	if ( ! is_object( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'object'" );
	}
	if ( null === $value ) {
		throwValidationError( $name, $value, $valueKind, "non-null" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 * @param array  $properties
 *
 * @return void
 * @throws \Exception
 */
function validateOptionalObject( string $name, $value, string $valueKind, array $properties ): void {
	if ( ! $value ) {
		return;
	}
	validateObject( $name, $value, $valueKind );

	/** @var \Skynet\Entity $value */

	foreach ( array_keys( $value->toArray() ) as $property ) {
		if ( ! isset( $properties[ $property ] ) ) {
			throw new Exception( sprintf( "Object %s '%s' contains unexpected property '%s'", $valueKind, $name, $property ) );
		}
	}

	if ( ! is_bool( $value ) ) {
		throwValidationError( $name, $value, $valueKind, "type 'boolean'" );
	}
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 * @param string $expected
 *
 * @return void
 * @throws \Exception
 */
function throwValidationError( string $name, $value, string $valueKind, string $expected ): void {
	$actualValue = '';

	if ( null === $value ) {
		$actualValue = "type 'null'";
	} else {
		$type        = gettype( $value );
		$actualValue = sprintf( "type '%s', value %s", $type, is_string( $value ) ? $value : var_export( $value, true ) );
	}

	throw new Exception( sprintf( "Expected '%s', '%s', to be %s, was %s", $valueKind, $name, $expected, $actualValue ) );
}

/**
 * @param string $name
 * @param        $value
 * @param string $valueKind
 *
 * @return string
 * @throws \Exception
 */
function validateSkylinkString( string $name, $value, string $valueKind ): string {
	$parsedSkylink = parseSkylink( $value );

	if ( null === $parsedSkylink ) {
		throwValidationError( $name, $value, $valueKind, "valid skylink of type 'string" );
	}

	return $parsedSkylink;
}

/**
 * @param \BN\BN $int
 *
 * @return void
 * @throws \Exception
 */
function assertUint64( BN $int ): void {
	validateBigint( "int", $int, "parameter" );

	if ( ( new BN( 0 ) )->gt( $int ) ) {
		throw new Exception( sprintf( 'Argument %s must be an unsigned 64-bit integer; was negative', $int->toString() ) );
	}

	if ( $int->gt( Registry::getMaxRevision() ) ) {
		throw new Exception( sprintf( 'Argument %sdoes not fit in a 64-bit unsigned integer; exceeds 2^64-1', $int->toString() ) );
	}
}


/**
 * @param string                      $name
 * @param \Skynet\Types\RegistryEntry $value
 * @param string                      $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validateRegistryEntry( string $name, RegistryEntry $value, string $valueKind ) {
	validateObject( $name, $value, $valueKind );
	validateString( "{$name}->dataKey", $value->getDataKey(), "{$valueKind} field" );
	validateUint8Array( "{$name}->data", $value->getData(), "{$valueKind} field" );
	validateBigint( "{$name}->revision", $value->getRevision(), "{$valueKind} field" );
}

/**
 * @param string $name
 * @param string $publicKey
 * @param string $valueKind
 *
 * @return void
 * @throws \Exception
 */
function validatePublicKey( string $name, string $publicKey, string $valueKind ) {
	if ( ! isHexString( trimPrefix( $publicKey, ED25519_PREFIX ) ) ) {
		throwValidationError( $name, $publicKey, $valueKind, 'a hex-encoded string with a valid prefix' );
	}
}
