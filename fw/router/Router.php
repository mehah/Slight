<?php
namespace fw\router;

final class Router {

	private static $list = array();

	public static function get(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		self::registerController('GET', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function post(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null) {
		self::registerController('POST', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function put(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null) {
		self::registerController('PUT', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	public static function delete(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null) {
		self::registerController('DELETE', $urlPath, $controllerClass, $methodName, $accessRule);
	}
	
	public static function patch(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null) {
		self::registerController('PATCH', $urlPath, $controllerClass, $methodName, $accessRule);
	}	
	
	public static function options(string $urlPath, string $controllerClass, string $methodName, array $accessRule = null) {
		self::registerController('OPTIONS', $urlPath, $controllerClass, $methodName, $accessRule);
	}

	private static function registerController(string $methodType, string $urlPath, string $controllerClass, string $methodName, array $accessRule = null): void {
		if (! $urlPath || ! $controllerClass) {
			throw new \Exception();
		}
		
		if (! is_file(str_replace('\\', '/', $controllerClass) . '.php')) {
			throw new \Exception('Controlador não encontrado: ' . $controllerClass);
		}
		
		$reflectionClass = new \ReflectionClass($controllerClass);
		
		if (! $reflectionClass->hasMethod($methodName)) {
			throw new \Exception('Método \'' . $methodName . '\' não registrado no controlador: ' . $controllerClass);
		}
		
		$config['controllerClass'] = $controllerClass;
		$config['methodName'] = $methodName;
		$config['accessRule'] = $accessRule;
		
		if (! ($list = &self::$list[$methodType])) {
			$list = array();
			self::$list[$methodType] = &$list;
		}
		
		$last = &$list;
		
		$ex = explode('/', $urlPath);
		foreach ($ex as $i => $value) {
			if (! $value) {
				continue;
			}
			
			if ($value[0] === ':') {
				if ($i === 0) {
					throw new \Exception('Primeira caractere não pode ser um parametro.');
				}
				
				$name = substr($value, 1);
				
				if (isset($last['$param']) && $last['$param']['name'] !== $name) {
					throw new \Exception('Não possivel registrar uma URL com nome do parametro diferente de uma URL parecida.');
				}
				
				$last['$param']['name'] = $name;
				$last = &$last['$param'];
				continue;
			}
			
			if (! isset($last[$value])) {
				$last[$value] = array();
			}
			
			$last = &$last[$value];
		}
		
		$last['config'] = &$config;
	}

	public static function getConfig(string $url): ?array {
		$router = &self::$list[$_SERVER['REQUEST_METHOD']];
		if (! $router) {
			return null;
		}
		
		$last = $router;
		$ex = explode('/', $url);
		foreach ($ex as $i => $value) {
			if (! $value) {
				continue;
			}
			
			if (isset($last[$value])) {
				$last = $last[$value];
			} else if (isset($last['$param'])) {
				$_REQUEST[$last['$param']['name']] = $value;
				$last = $last['$param'];
			}
		}
		
		if (! $last || ! isset($last['config'])) {
			return null;
		}
		
		return $last['config'];
	}
}

