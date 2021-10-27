<?php

namespace Skynet\functions\options;

use Exception;
use ReflectionClass;
use Skynet\Entity;
use Skynet\Options\CustomClientOptions;
use Skynet\Options\CustomDownloadOptions;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Options\CustomGetJSONOptions;
use Skynet\Options\CustomGetMetadataOptions;
use Skynet\Options\CustomHnsDownloadOptions;
use Skynet\Options\CustomPinOptions;
use Skynet\Options\CustomSetEntryOptions;
use Skynet\Options\CustomSetJSONOptions;
use Skynet\Options\CustomUploadOptions;
use Skynet\Options\CustomValidateRegistryProofOptions;
use Skynet\Options\ParseSkylinkEntity;
use Skynet\Options\Request;
use Skynet\Types\EncryptedFileMetadata;

/**
 * @param string $class
 * @param array  $args
 *
 * @return mixed
 * @throws \Exception
 */
function makeOptions( string $class, array $args ) {
	if ( ! class_exists( $class ) ) {
		throw  new Exception( sprintf( 'Class %s does not exist!', $class ) );
	}
	if ( ! is_subclass_of( $class, Entity::class ) ) {
		throw  new Exception( sprintf( 'Class %s does not inherit %s to build options!', $class, Entity::class ) );
	}
	/** @var \Skynet\Entity $obj */
	$obj = new $class( $args );

	return $obj;
}

/**
 * @param array $args
 *
 * @return \Skynet\Options\Request
 * @throws \Exception
 */
function makeRequest( array $options ): Request {
	return makeOptions( Request::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomClientOptions
 * @throws \Exception
 */
function makeClientOptions( array $options ): CustomClientOptions {
	return makeOptions( CustomClientOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomDownloadOptions
 * @throws \Exception
 */
function makeDownloadOptions( array $options ): CustomDownloadOptions {
	return makeOptions( CustomDownloadOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomHnsDownloadOptions
 * @throws \Exception
 */
function makeHnsDownloadOptions( array $options ): CustomHnsDownloadOptions {
	return makeOptions( CustomHnsDownloadOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomGetMetadataOptions
 * @throws \Exception
 */
function makeGetMetadataOptions( array $options ): CustomGetMetadataOptions {
	return makeOptions( CustomGetMetadataOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\ParseSkylinkEntity
 * @throws \Exception
 */
function makeParseSkylinkOptions( array $options ): ParseSkylinkEntity {
	return makeOptions( ParseSkylinkEntity::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomValidateRegistryProofOptions
 * @throws \Exception
 */
function makeValidateRegistryProofOptions( array $options ): CustomValidateRegistryProofOptions {
	return makeOptions( CustomValidateRegistryProofOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomGetEntryOptions
 * @throws \Exception
 */
function makeGetEntryOptions( array $options ): CustomGetEntryOptions {
	return makeOptions( CustomGetEntryOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomSetEntryOptions
 * @throws \Exception
 */
function makeSetEntryOptions( array $options ): CustomSetEntryOptions {
	return makeOptions( CustomSetEntryOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomGetJSONOptions
 * @throws \Exception
 */
function makeGetJSONOptions( array $options ): CustomGetJSONOptions {
	return makeOptions( CustomGetJSONOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomSetJSONOptions
 * @throws \Exception
 */
function makeSetJSONOptions( array $options ): CustomSetJSONOptions {
	return makeOptions( CustomSetJSONOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomPinOptions
 * @throws \Exception
 */
function makePinOptions( array $options ): CustomPinOptions {
	return makeOptions( CustomPinOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Options\CustomUploadOptions
 * @throws \Exception
 */
function makeUploadOptions( array $options ): CustomUploadOptions {
	return makeOptions( CustomUploadOptions::class, $options );
}

/**
 * @param array $options
 *
 * @return \Skynet\Types\EncryptedFileMetadata
 * @throws \Exception
 */
function makeEncryptedFileMetadata( array $options ): EncryptedFileMetadata {
	return makeOptions( EncryptedFileMetadata::class, $options );
}


/**
 * @param ...$args
 *
 * @return array
 */
function mergeOptions( ...$args ): array {
	$newArgs = [];

	foreach ( $args as $arg ) {
		if ( null === $arg ) {
			continue;
		}
		if ( $arg instanceof Entity ) {
			$newArg = $arg->toArray();
			foreach ( $newArg as $newArgKey => $newArgVal ) {
				if ( null === $newArgVal ) {
					unset( $newArg[ $newArgKey ] );
				}
			}
			$newArgs[] = $newArg;
		}
		if ( is_array( $arg ) ) {
			$newArg = $arg;
			foreach ( $newArg as $newArgKey => $newArgVal ) {
				if ( null === $newArgVal ) {
					unset( $newArg[ $newArgKey ] );
				}
			}
			$newArgs[] = $newArg;
		}
	}

	return array_merge( ...$newArgs );
}

/**
 * @param \Skynet\Entity $options
 * @param array          $entity
 *
 * @return array
 */
function extractOptions( Entity $options, array $entity ): array {
	$result = [];
	$ref    = new ReflectionClass( $options );
	foreach ( $entity as $property => $value ) {
		if ( ! $ref->hasProperty( $property ) ) {
			continue;
		}

		if ( null !== $options[ $property ] ) {
			$result[ $property ] = $options[ $property ];
		}
	}

	return $result;
}
