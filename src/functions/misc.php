<?php

namespace Skynet\functions\misc;

function arrayToObject( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	if ( is_numeric( key( $data ) ) ) {
		return array_map( __FUNCTION__, $data );
	}

	return (object) array_map( __FUNCTION__, $data );
}
