<?php

namespace Skynet\Options;

use Skynet\Entity;

/**
 *
 */
class ParseSkylinkEntity extends Entity {
	/**
	 * @var bool
	 */
	protected bool $fromSubdomain;
	/**
	 * @var bool
	 */
	protected bool $includePath;
	/**
	 * @var bool
	 */
	protected bool $onlyPath;

	/**
	 * @return bool
	 */
	public function isFromSubdomain(): bool {
		return $this->fromSubdomain;
	}

	/**
	 * @return bool
	 */
	public function isIncludePath(): bool {
		return $this->includePath;
	}

	/**
	 * @return bool
	 */
	public function isOnlyPath(): bool {
		return $this->onlyPath;
	}
}
