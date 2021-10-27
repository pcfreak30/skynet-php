<?php

namespace Skynet\Traits;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Requests;
use Skynet\Entity;
use Skynet\Options\CustomClientOptions;
use Skynet\Options\CustomGetEntryOptions;
use Skynet\Options\Request;
use Skynet\Registry;
use function Skynet\functions\options\makeOptions;
use function Skynet\functions\options\mergeOptions;
use function Skynet\functions\url\add_query_arg;
use function Skynet\functions\url\makeUrl;

/**
 *
 */
trait BaseMethods {
	/**
	 * @var \Skynet\Options\CustomClientOptions|null
	 */
	private CustomClientOptions $options;

	/**
	 * @var \GuzzleHttp\Client|null
	 */
	private ?Client $httpClient;

	/**
	 * @param \Skynet\Options\Request $request
	 *
	 * @return Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected
	function executeRequest(
		Request $request
	): Response {
		$url     = $this->buildRequestUrl( $request->getEndpointPath(), $request->getUrl(), $request->getExtraPath(), $request->getQuery() );
		$headers = $this->buildRequestHeaders( $request->getHeaders(), $request->getCustomUserAgent(), $request->getCustomCookie() );

		$options = $request->getOptions();

		if ( $request->getApiKey() ) {
			$options['auth'] = [ '', $request->getApiKey(), 'basic' ];
		}

		if ( ! isset( $headers['Cookie'] ) && ! empty( $this->sessionKey ) ) {
			$headers['Cookie'] = "skynet-jwt={$this->sessionKey}";
		}

		$data      = $request->getData();
		$dataField = 'body';
		if ( is_array( $data ) || is_object( $data ) ) {
			$dataField = 'json';
		} elseif ( null === $data ) {
			$data = null;
		}

		$response = $this->getHttpClient()->request( $request->getMethod(), $url, array_merge( [
			'headers'  => $headers,
			$dataField => $data,
		], $options ) );

		return $response;
	}

	/**
	 * @param string      $endpointPath
	 * @param string|null $url
	 * @param string|null $extraPath
	 * @param array       $query
	 *
	 * @return string|null
	 */
	public
	function buildRequestUrl(
		string $endpointPath, ?string $url = null, ?string $extraPath = null, ?array $query = null
	) {
		if ( null === $url ) {
			if ( method_exists( $this, 'getSkynet' ) ) {
				$url = $this->getSkynet()->getPortalUrl();
			} else {
				$url = $this->getPortalUrl();
			}
			$url = makeUrl( $url, $endpointPath, $extraPath ?? '' );
		}

		if ( null !== $query && 0 < count( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		return $url;
	}

	/**
	 * @param array       $baseHeaders
	 * @param string|null $customUserAgent
	 * @param string|null $customCookie
	 *
	 * @return array
	 */
	private
	function buildRequestHeaders(
		array $baseHeaders = [], ?string $customUserAgent = null, ?string $customCookie = null
	) {
		$returnHeaders = $baseHeaders;

		if ( $customUserAgent ) {
			$returnHeaders['User-Agent'] = $customUserAgent;
		}
		if ( $customCookie ) {
			$returnHeaders['Cookie'] = $customCookie;
		}

		return $returnHeaders;
	}

	/**
	 * @return \GuzzleHttp\Client
	 */
	public function getHttpClient(): Client {
		if ( ! isset( $this->httpClient ) ) {
			$this->httpClient = new Client();
		}

		return $this->httpClient;
	}

	/**
	 * @param \GuzzleHttp\Client $httpClient
	 */
	public function setHttpClient( Client $httpClient ): void {
		$this->httpClient = $httpClient;
	}

	/**
	 * @param array|null $options
	 * @param array      $funcOptions
	 *
	 * @return \Skynet\Options\Request
	 */
	private
	function buildRequestOptions(
		array $options = null, array $funcOptions = []
	): Request {
		/** @noinspection PhpParamsInspection */
		return $this->buildOptions( [], Request::class, null, mergeOptions( $options, $funcOptions ) );
	}

	/**
	 * @param array               $defaults
	 * @param                     $class
	 * @param \Skynet\Entity|null $options
	 * @param array               $funcOptions
	 *
	 * @return \Skynet\Entity
	 * @throws \Exception
	 */
	private function buildOptions( array $defaults, $class, ?Entity $options = null, array $funcOptions = [] ): Entity {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		$options = mergeOptions(
			$defaults,
			$this->options,
			$options,
			$funcOptions
		);

		return makeOptions( $class, $options );
	}

	/**
	 * @param \Skynet\Options\CustomGetEntryOptions|null $options
	 * @param array                                      $funcOptions
	 *
	 * @return \Skynet\Options\CustomGetEntryOptions
	 */
	private function buildGetEntryOptions( CustomGetEntryOptions $options = null, array $funcOptions = [] ): CustomGetEntryOptions {
		/** @noinspection CallableParameterUseCaseInTypeContextInspection */
		return $this->buildOptions( Registry::DEFAULT_GET_ENTRY_OPTIONS, CustomGetEntryOptions::class, $options, $funcOptions );
	}

}
