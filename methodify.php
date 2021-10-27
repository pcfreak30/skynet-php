<?php

$words = array_slice( $argv, 1 );
$words = preg_replace( '/[^\w]*/', '', $words );
$words = array_map( 'strtolower', $words );
$words = array_map( 'ucfirst', $words );

echo implode( '', $words ) . "\n";
