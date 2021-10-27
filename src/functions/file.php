<?php
namespace Skynet\functions\file;

use Skynet\Types\File;

/**
 * @param string $file
 *
 * @return \Skynet\Types\File
 */
function getFileFromPath( string $file ): File {
	$info = new \SplFileInfo( $file );

	return new File( [
		'fileName' => $info->getFilename(),
		'filePath' => $info->getPath(),
	] );
}

/**
 * @param string      $data
 * @param string      $fileName
 * @param string|null $filePath
 * @param string|null $mime
 *
 * @return \Skynet\Types\File
 */
function getFileFromData( string $data, string $fileName, string $filePath = null, string $mime = null ): File {
	return new File( [
		'fileName' => $fileName,
		'filePath' => $filePath,
		'data'     => $data,
	] );
}
