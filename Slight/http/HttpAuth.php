<?php
namespace Slight\http;

use Slight\router\Route;

interface HttpAuth {

	public static function onAuthentication(Route $router): bool;
}