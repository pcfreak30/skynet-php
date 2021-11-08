<?php

namespace Skynet\Types;

use GuzzleHttp\Psr7\MimeType;
use Psr\Http\Message\StreamInterface;
use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class File extends Entity {
	/**
	 * @var \Skynet\Uint8Array|null
	 */
	protected ?Uint8Array $data = null;
	/**
	 * @var string|null
	 */
	protected ?string $fileName = null;
	/**
	 * @var string|null
	 */
	protected ?string $filePath = null;

	/**
	 * @var string|null
	 */
	protected ?StreamInterface $stream = null;
	/**
	 * @var string|null
	 */
	protected ?string $mime = null;

	/**
	 * @param      $resource
	 * @param null $fileName
	 *
	 * @return static
	 * @throws \Exception
	 */
	public static function fromResource( $resource, $fileName = null ): self {
		$file = new self;
		if ( is_string( $resource ) ) {
			if ( file_exists( $resource ) ) {
				$file->setFileName( $resource );
				$mime = MimeType::fromFilename( $resource );
				if ( $mime ) {
					$file->setMime( $mime );
				}
			} else {
				$file->setData( Uint8Array::from( $resource ) );
				$file->setFileName( $fileName );
			}
		}

		if ( $resource instanceof StreamInterface ) {
			$file->setFileName( $fileName );
			$file->setStream( $resource );
			$mime = MimeType::fromFilename( $fileName );
			if ( $mime ) {
				$file->setMime( $mime );
			}
		}

		if ( is_resource( $resource ) ) {
			$metaData = \stream_get_meta_data( $resource );
			$file->setFileName( $metaData['uri'] );
			$mime = MimeType::fromFilename( $resource );
			if ( $mime ) {
				$file->setMime( $mime );
			}
		}

		if ( empty( $file->toArray() ) ) {
			throw new \Exception( 'Invalid resource input' );
		}

		return $file;
	}

	/**
	 * @return \Skynet\Uint8Array|null
	 */
	public function getData(): ?Uint8Array {
		return $this->data;
	}

	/**
	 * @param \Skynet\Uint8Array|null $data
	 */
	public function setData( ?Uint8Array $data ): void {
		$this->data = $data;
	}

	/**
	 * @return string|null
	 */
	public function getFileName(): ?string {
		return $this->fileName;
	}

	/**
	 * @param string|null $fileName
	 */
	public function setFileName( string $fileName ): void {
		$dirname = pathinfo( $fileName, PATHINFO_DIRNAME );
		$dirname = trim( $dirname, '.' );
		if ( $dirname ) {
			$fileName       = pathinfo( $fileName, PATHINFO_BASENAME );
			$this->filePath = $dirname;
		} else {
			$this->filePath = null;
		}
		$this->fileName = $fileName;
	}

	/**
	 * @return string|null
	 */
	public function getFilePath(): ?string {
		return $this->filePath;
	}

	/**
	 * @param string|null $filePath
	 */
	public function setFilePath( ?string $filePath ): void {
		$this->filePath = $filePath;
	}

	/**
	 * @return int
	 */
	public function getFileSize(): int {
		if ( isset( $this->stream ) ) {
			return $this->stream->getSize();
		}

		if ( isset( $this->filePath ) ) {
			return filesize( $this->filePath );
		}

		if ( isset( $this->data ) ) {
			return $this->data->getMaxLength();
		}

		return 0;
	}

	/**
	 * @return string|null
	 */
	public function getMime(): ?string {
		return $this->mime;
	}

	/**
	 * @param string|null $mime
	 */
	public function setMime( ?string $mime ): void {
		$this->mime = $mime;
	}

	/**
	 * @return string|null
	 */
	public function getStream() {
		return $this->stream;
	}

	/**
	 * @param string|null $stream
	 */
	public function setStream( $stream ): void {
		$this->stream = $stream;
	}
}
