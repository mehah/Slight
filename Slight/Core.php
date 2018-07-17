<?php
namespace Slight;

use Slight\http\HttpSession;
use Slight\router\Router;

abstract class Core {

	private const PATH_SRC = 'src';

	private const PATH_BUILD = 'build';

	private const PATH_VIEW = 'view';

	private const PATH_PROJECT_CONFIG = self::PATH_SRC . '/config.php';

	static function init(): void {
		$APP_URL = $_REQUEST['$url'] ?? null;
		if (! $APP_URL) {
			$viewPath = self::PATH_VIEW . '/index.html';
			if (is_file($viewPath)) {
				readfile($viewPath);
				exit();
			}
			
			exit('PUT index.html in view folder.');
		}
		
		if (is_file(self::PATH_PROJECT_CONFIG)) {
			include self::PATH_PROJECT_CONFIG;
		}
		
		if (! is_file($pathRouter = self::PATH_SRC . '/router.php')) {
			throw new \Exception('The route configuration file could not be found at: src/router.php');
		}
		
		if (! is_dir(self::PATH_BUILD)) {
			mkdir(self::PATH_BUILD, 0777, true);
		}
		
		if (Config::cachedRouter()) {
			$pathRouterSource = self::PATH_BUILD . '/router.src';
			
			$propList = new \ReflectionProperty(Router::class, 'list');
			$propList->setAccessible(true);
			
			if (! is_file($pathRouterSource) || filemtime($pathRouter) > filemtime($pathRouterSource)) {
				include $pathRouter;
				file_put_contents($pathRouterSource, serialize($propList->getValue()));
			} else {
				$propList->setValue(unserialize(file_get_contents($pathRouterSource)));
			}
			
			$propList->setAccessible(false);
		} else {
			include $pathRouter;
		}
		
		if (substr($APP_URL, - 1) === '/') {
			$APP_URL = substr($APP_URL, 0, - 1);
		}
		
		$router = Router::getRoute($APP_URL);
		
		if (! $router) {
			http_response_code(404);
			exit("ROUTER '$APP_URL' NOT FOUND.");
		}
		
		$authClass = $router->getAuthClass() ?? Config::getDefaultAuthClass();
		if ($authClass === null && ! self::hasAccess($router->getRules(), $router->getMethodName()) || $authClass !== null && ! $authClass::onAuthentication($router)) {
			http_response_code(401);
			exit('You are not authorized to execute this action.');
		}
		
		$controllerClass = $router->getControllerClass();
		if (Config::isStatefulClass($controllerClass)) {
			$session = &HttpSession::getInstance();
			$controller = $session[$controllerClass] ?? null;
			
			$sessionAttrName = '__'.$controllerClass;
			if ($session->hasAttribute($sessionAttrName)) {
				$controller = &$session->getAttribute($sessionAttrName);
			} else {
				$controller = new $controllerClass();
				$session->setAttribute($sessionAttrName, $controller);
			}
		} else {
			$controller = new $controllerClass();
		}
		
		$reflectionMethod = new \ReflectionMethod($controllerClass, $router->getMethodName());
		if (count($params = $reflectionMethod->getParameters()) > 0) {
			$list = [];
			foreach ($params as $param) {
				if ($param->getClass() && HttpSession::class === $param->getClass()->getName()) {
					$argValue = &$session || HttpSession::getInstance();
				} elseif ($argValue = ($_REQUEST[$param->getName()] ?? null)) {
					$classType = $param->getType();
					if ($classType && ! $classType->isBuiltin()) {
						$classType = $classType->getName();
						$object = new $classType();
						
						self::arrayToObject($argValue, $object);
						$argValue = $object;
					}
				}
				
				$list[] = $argValue;
			}
			
			$methodResult = $reflectionMethod->invokeArgs($controller, $list);
		} else {
			$methodResult = $reflectionMethod->invoke($controller);
		}
		
		if ($methodResult !== null) {
			header('Content-type:application/json;charset=' . Config::getChatset());
			echo json_encode($methodResult);
		}
	}

	private static function hasAccess(?Array $rules, String $methodName): bool {
		if (! $rules) {
			return true;
		}
		
		if ($user = self::getSessionInstance()->getUserPrincipal()) {
			foreach ($rules as $ruleName) {
				if (in_array($ruleName, $user->getRules())) {
					return true;
				}
			}
		}
		
		return false;
	}

	private static function arrayToObject($data, $object): void {
		$defaults = (new \ReflectionClass($object))->getDefaultProperties();
		
		foreach ($defaults as $key => $value) {
			if (array_key_exists($key, $data)) {
				$value = &$data[$key];
				if ($_ref = $object->{$key}) {
					self::arrayToObject($value, $_ref);
				} else {
					$object->{$key} = $value ?? null;
				}
			}
		}
	}
}