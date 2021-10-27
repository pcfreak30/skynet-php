<?php

namespace Skynet\Options;


use Skynet\Entity;

/**
 *
 */
class CustomClientOptions extends Entity {
	/**
	 * @var string|null
	 */
	protected $apiKey;
	/**
	 * @var string|null
	 */
	protected $customUserAgent;
	/**
	 * @var string|null
	 */
	protected $customCookie;

	/**
	 * @var array
	 */
	protected array $extraOptions = [];

	/**
	 * @return array
	 */
	public function getExtraOptions(): array {
		return $this->extraOptions;
	}

	/**
	 * @param array $extraOptions
	 */
	public function setExtraOptions( array $extraOptions ): void {
		$this->extraOptions = $extraOptions;
	}

	/**
	 * @return string
	 */
	public function getApiKey() {
		return $this->apiKey;
	}

	/**
	 * @param null $apiKey
	 */
	public function setApiKey( $apiKey ) {
		$this->apiKey = $apiKey;
	}

	/**
	 * @return string
	 */
	public function getCustomUserAgent() {
		return $this->customUserAgent;
	}

	/**
	 * @param string $customUserAgent
	 */
	public function setCustomUserAgent( $customUserAgent ) {
		$this->customUserAgent = $customUserAgent;
	}

	/**
	 * @return string
	 */
	public function getCustomCookie() {
		return $this->customCookie;
	}

	/**
	 * @param null $customCookie
	 */
	public function setCustomCookie( $customCookie ) {
		$this->customCookie = $customCookie;
	}
}
