<?php

use Mockery\Loader\RequireLoader;

Mockery::globalHelpers();
Mockery::setLoader( new RequireLoader( sys_get_temp_dir() ) );

require_once __DIR__ . '/testing.php';
