<?php

namespace _support;

use Codeception\MockeryModule\Test;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Mockery;
use Mockery\MockInterface;
use Skynet\Skynet;
use const Skynet\DEFAULT_SKYNET_PORTAL_URL;

class BaseTest extends Test {
	protected string $skylink = 'XABvi7JtJbQSMAcDwnUnmp2FKDPjg8_tTTFP4BwMSxVdEg';
	protected $portalUrl = DEFAULT_SKYNET_PORTAL_URL;
	protected string $skylinkMetaDataUrl;
	protected MockInterface $requestsMock;
	protected array $requestsArgs = [];
	protected Skynet $client;
	protected $requestHistory = [];

	protected function _before() {
		$this->skylinkMetaDataUrl = "{$this->portalUrl}/skynet/metadata/{$this->skylink}";
		$this->client             = new Skynet( $this->portalUrl );
		$mock                     = Mockery::fetchMock( 'Requests' );
		if ( ! $mock ) {
			$mock = mock( '\GuzzleHttp\Client' );
		}
		$this->requestsMock = $mock;
		$this->client->setHttpClient( $mock);
	}

	protected function getRequestMockSucessfulReturn( $body = [], array $headers = [], $callArgs = [] ) {
		return $this->getRequestMock(  ...$callArgs )
		            ->andReturn( $this->buildSucessfulRequestResponse( $body, $headers ) );
	}

	protected function getRequestMock( ...$args ) {
		if ( empty( $args ) ) {
			$args = [ $this->skylinkMetaDataUrl ];
		}

		$exp = $this->requestsMock
			->shouldReceive( 'request' );

		if ( is_callable( $args[0] ) ) {
			/** @var callable $args */
			$exp->withArgs( $args[0] );
		} else {
			/** @var array $args */
			$exp->withSomeOfArgs( ...$args );
		}

		return $exp;
	}

	protected function buildSucessfulRequestResponse($body = [], array $headers = [] ): Response {
		return $this->buildRequestResponse( 200, $body, $headers );
	}

	protected function buildRequestResponse( int $code, $body = [], array $headers = [] ): Response {
		if ( null !== $body ) {
			if ( is_array( $body ) || is_object( $body ) ) {
				$body = json_encode( $body );
			}
		}

		return new Response($code, $headers, $body);
	}

	protected function getRequestMockBadRequestReturn( $body = [], array $headers = [], $callArgs = [] ) {
		return $this->getRequestMockWithReturn( $this->buildBadRequestMetadataResponse( $body, $headers ), $callArgs );
	}

	protected function getRequestMockWithReturn( Response $response, $callArgs = [] ) {
		return $this->getRequestMock( ...$callArgs )
		            ->andReturn( $response );
	}

	protected function buildBadRequestMetadataResponse( $body = [], array $headers = [] ): Response {
		return $this->buildRequestResponse( 400, $body, $headers );
	}

	protected function getRequestMockSucessfulReturnCaptureArgs( ?array $body = [], array $headers = [] ) {
		return $this->getRequestMock( function ( ...$args ) {
			$this->requestsArgs = $args;

			return true;
		} )->andReturn( $this->buildSucessfulRequestResponse( $body, $headers ) );
	}

}
