<?php
namespace Slight\http;

use Slight\router\RouterHandler;

interface HttpAuth {

	public static function onAuthentication(RouterHandler $router): bool;
}