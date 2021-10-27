<?php

namespace Skynet\functions\tweak;

use Skynet\DiscoverableBucketTweak;
use Skynet\Uint8Array;
use function Skynet\functions\crypto\hashAll;
use function Skynet\functions\formatting\toHexString;
use function Skynet\functions\strings\stringToUint8ArrayUtf8;

/**
 * @param string $path
 *
 * @return string
 * @throws \SodiumException
 */
function deriveDiscoverableFileTweak( string $path ): string {
	$dbt   = new DiscoverableBucketTweak( $path );
	$bytes = $dbt->getHash();

	return toHexString( $bytes );
}

/**
 * @param string $component
 *
 * @return \Skynet\Uint8Array
 * @throws \SodiumException
 */
function hashPathComponent( string $component ): Uint8Array {
	return hashAll( stringToUint8ArrayUtf8( $component ) );
}
