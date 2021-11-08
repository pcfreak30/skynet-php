<?php

namespace Skynet\Options;

/**
 *
 */
class Request extends CustomClientOptions {
	/**
	 * @var string|null
	 */
	protected ?string $endpointPath;
	/**
	 * @var null
	 */
	protected $data = null;
	/**
	 * @var string|null
	 */
	protected ?string $url = null;
	/**
	 * @var string
	 */
	protected string $method;
	/**
	 * @var array
	 */
	protected array $headers = [];
	/**
	 * @var array
	 */
	protected array $query = [];
	/**
	 * @var string
	 */
	protected string $extraPath = '';
	/**
	 * @var string
	 */
	protected string $responseType;
	/**
	 * @var array
	 */
	protected array $options = [];

	/**
	 * @return string
	 */
	public function getEndpointPath(): ?string {
		return $this->endpointPath ?? null;
	}

	/**
	 * @param string $endpointPath
	 */
	public function setEndpointPath( string $endpointPath ): void {
		$this->endpointPath = $endpointPath;
	}

	/**
	 * @return ?array|string
	 */
	public function getData() {
		return $this->data;
	}

	/**
	 * @param ?array|string $data
	 */
	public function setData( $data ) {
		$this->data = $data;
	}

	/**
	 * @return string
	 */
	public function getUrl(): ?string {
		return $this->url;
	}

	/**
	 * @param string $url
	 */
	public function setUrl( string $url ): void {
		$this->url = $url;
	}

	/**
	 * @return string
	 */
	public function getMethod(): string {
		return $this->method;
	}

	/**
	 * @param string $method
	 */
	public function setMethod( string $method ): void {
		$this->method = strtoupper( $method );
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array {
		return $this->headers;
	}

	/**
	 * @param array $headers
	 */
	public function setHeaders( array $headers ): void {
		$this->headers = $headers;
	}

	/**
	 * @return array
	 */
	public function getQuery(): array {
		return $this->query;
	}

	/**
	 * @param array $query
	 */
	public function setQuery( array $query ): void {
		$this->query = $query;
	}

	/**
	 * @return string
	 */
	public function getExtraPath(): string {
		return $this->extraPath;
	}

	/**
	 * @param string $extraPath
	 */
	public function setExtraPath( string $extraPath ): void {
		$this->extraPath = $extraPath;
	}

	/**
	 * @return string
	 */
	public function getResponseType(): string {
		return $this->responseType;
	}

	/**
	 * @param string $responseType
	 */
	public function setResponseType( string $responseType ): void {
		$this->responseType = $responseType;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array {
		return $this->options;
	}

	/**
	 * @param array $options
	 */
	public function setOptions( array $options ): void {
		$this->options = $options;
	}

}
