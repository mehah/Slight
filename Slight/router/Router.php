<?php
namespace Slight\router;

final class Router {

	private static $list = [];

	public static function get(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('GET', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function post(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('POST', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function put(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('PUT', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function delete(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('DELETE', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function patch(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('PATCH', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function options(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::register('OPTIONS', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	private static function register(string $methodType, string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		if (filter_var('http://' . $urlPath, FILTER_VALIDATE_URL) === false) {
			throw new \Exception('Invalid URL: ' . $urlPath);
		}
		
		if (! is_file(str_replace('\\', '/', $controllerClass) . '.php')) {
			throw new \Exception('Controller not found: ' . $controllerClass);
		}
		
		$reflectionClass = new \ReflectionClass($controllerClass);
		
		if (! $reflectionClass->hasMethod($methodName)) {
			throw new \Exception('Method \'' . $methodName . '\' not registered in controller: ' . $controllerClass);
		}
		
		$routerList = &self::$list[$methodType];
		if (! $routerList) {
			$routerList = [];
			self::$list[$methodType] = &$routerList;
		}
		
		if (strpos($urlPath, ':') === false) {
			$routerList[$urlPath] = [];
			$routerList = &$routerList[$urlPath];
		} else {
			foreach (explode('/', $urlPath) as $i => $value) {
				if (! $value) {
					continue;
				}
				
				if ($value[0] === ':') {
					if ($i === 0) {
						throw new \Exception('First character can not be a parameter.');
					}
					
					$name = substr($value, 1);
					
					if (isset($routerList['$param']) && $routerList['$param']['name'] !== $name) {
						throw new \Exception('It\'s not possible to register a URL with a parameter name different from one already registered.');
					}
					
					$routerList['$param']['name'] = $name;
					$routerList = &$routerList['$param'];
					continue;
				}
				
				if (! isset($routerList[$value])) {
					$routerList[$value] = [];
				}
				
				$routerList = &$routerList[$value];
			}
		}
		
		if (isset($routerList['config'])) {
			throw new \Exception("There is already a routing with the URL '$urlPath', pointing to controller('$controllerClass') and method('$methodName').");
		}
		
		$routerList['config'] = [
			'controllerClass' => $controllerClass,
			'methodName' => $methodName,
			'accessRule' => $accessRule
		];
	}

	public static function getConfig(string $url): ?array {
		$router = self::$list[$_SERVER['REQUEST_METHOD']] ?? null;
		if (! $router) {
			return null;
		}
		
		if (isset($router[$url])) {
			$router = $router[$url];
		} else {
			foreach (explode('/', $url) as $value) {
				if (! $value) {
					continue;
				}
				
				if (isset($router[$value])) {
					$router = $router[$value];
				} elseif ($router = $router['$param'] ?? null) {
					$_REQUEST[$router['name']] = $value;
				} else {
					break;
				}
			}
		}
		
		return $router['config'] ?? null;
	}
}