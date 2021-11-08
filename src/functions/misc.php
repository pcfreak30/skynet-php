<?php

namespace Skynet\functions\misc;

use Skynet\Entity;

function arrayToObject( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	if ( is_numeric( key( $data ) ) ) {
		return array_map( __FUNCTION__, $data );
	}

	return (object) array_map( __FUNCTION__, $data );
}

function entityToArray( Entity $entity ): array {
	$data = $entity->toArray();

	foreach ( $data as $name => $item ) {
		if ( $item instanceof Entity ) {
			$data[ $name ] = entityToArray( $item );
		}

		if ( is_array( $item ) ) {
			foreach ( $item as $index => $subItem ) {
				if ( $subItem instanceof Entity ) {
					$data[ $name ][ $index ] = entityToArray( $subItem );
				}
			}
		}
	}

	return $data;
}
