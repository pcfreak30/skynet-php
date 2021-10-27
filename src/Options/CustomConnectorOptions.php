<?php

namespace Skynet\Options;

use Skynet\Entity;

/**
 *
 */
class CustomConnectorOptions extends Entity{
	/**
	 * @var bool
	 */
	protected bool $dev;
	/**
	 * @var bool
	 */
	protected bool $debug;
	/**
	 * @var bool
	 */
	protected bool $alpha;
	/**
	 * @var int
	 */
	protected int $handshakeMaxAttempts;
	/**
	 * @var int
	 */
	protected int $handshakeAttemptsInterval;
}
