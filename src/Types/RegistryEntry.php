<?php

namespace Skynet\Types;

use BI\BigInteger;
use BN\BN;
use Skynet\Entity;
use Skynet\Uint8Array;

/**
 *
 */
class RegistryEntry extends Entity {
	/**
	 * @var string
	 */
	protected string $dataKey;
	/**
	 * @var \Skynet\Uint8Array
	 */
	protected Uint8Array $data;
	/**
	 * @var \BI\BigInteger
	 */
	protected BN $revision;


	/**
	 * @param string             $dataKey
	 * @param \Skynet\Uint8Array $data
	 * @param \BN\BN             $revision
	 */
	public function __construct( string $dataKey, Uint8Array $data, BN $revision ) {
		$this->dataKey  = $dataKey;
		$this->data     = $data;
		$this->revision = $revision;
		parent::__construct();
	}

	/**
	 * @return string
	 */
	public function getDataKey(): string {
		return $this->dataKey;
	}

	/**
	 * @param string $dataKey
	 */
	public function setDataKey( string $dataKey ): void {
		$this->dataKey = $dataKey;
	}

	/**
	 * @return \Skynet\Uint8Array
	 */
	public function getData(): Uint8Array {
		return $this->data;
	}

	/**
	 * @param \Skynet\Uint8Array $data
	 */
	public function setData( Uint8Array $data ): void {
		$this->data = $data;
	}

	/**
	 * @return \BI\BigInteger
	 */
	public function getRevision(): BN {
		return $this->revision;
	}

	/**
	 * @param \BN\BN $revision
	 */
	public function setRevision( BN $revision ): void {
		$this->revision = $revision;
	}
}
