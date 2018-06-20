<?php
namespace fw;

use fw\http\HttpSession;
use fw\router\Router;

abstract class Core {

	private const PATH_SRC = 'src';

	private const PATH_BUILD = 'build';

	private const PATH_PROJECT_CONFIG = self::PATH_SRC . '/config.php';

	static function init(): void {
		spl_autoload_register(function ($class_name) {
			include str_replace('\\', '/', $class_name) . '.php';
		});
		
		if (is_file(self::PATH_PROJECT_CONFIG)) {
			include self::PATH_PROJECT_CONFIG;
		}
		
		if (! is_file($pathRouter = self::PATH_SRC . '/router.php')) {
			throw new \Exception('The route configuration file could not be found at: src/router.php');
		}
		
		if (isset($_REQUEST['$url'])) {
			$APP_URL = $_REQUEST['$url'];
			$APP_URL = substr($APP_URL, - 1) === '/' ? substr($APP_URL, 0, - 1) : $APP_URL;
		} else {
			$APP_URL = '/';
		}
		
		if (! is_dir(self::PATH_BUILD)) {
			mkdir(self::PATH_BUILD, 0777, true);
		}
		
		if (Project::cachedRouter()) {
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
		
		$config = Router::getConfig($APP_URL);
		
		if (! $config) {
			http_response_code(404);
			exit('ROUTER NOT CONFIGURED');
		}
		
		$requestedMethod = $config['methodName'];
		
		session_start();
		if (($accessRule = $config['accessRule']) && ! self::hasAccess($accessRule, $requestedMethod)) {
			http_response_code(401);
			exit('You are not authorized to execute this action.');
		}
		
		$controllerClass = $config['controllerClass'];
		$session = &self::getSession();
		
		$controller = $session[$controllerClass] ?? null;
		
		if (! $controller) {
			$controller = new $controllerClass();
			if (! ($controller instanceof ComponentController)) {
				exit('The controller ' . $controllerClass . 'need to extend the ComponentController class.');
			}
			$session[$controllerClass] = $controller;
		}
		
		$reflectionMethod = new \ReflectionMethod($controllerClass, $requestedMethod);
		
		$methodResult;
		if (count($params = $reflectionMethod->getParameters()) > 0) {
			$list = Array();
			foreach ($params as $param) {
				$classType = $param->getType();
				
				if ($classType && ! $classType->isBuiltin()) {
					$arg = new $classType->getName();
					self::setClassProps($_REQUEST[$param->getName()], $arg);
					
					$list[] = $arg;
				} elseif ($arg = ($_REQUEST[$param->getName()] ?? null)) {
					$list[] = $arg;
				}
			}
			
			$methodResult = $reflectionMethod->invokeArgs($controller, $list);
		} else {
			$methodResult = $reflectionMethod->invoke($controller);
		}
		
		if ($methodResult) {
			echo json_encode($methodResult);
		}
	}

	private static function hasAccess(Array $rules, String $methodName): bool {
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

	private static function setClassProps($data, $object): void {
		$defaults = (new \ReflectionClass($object))->getDefaultProperties();
		
		foreach ($defaults as $key => $value) {
			if (array_key_exists($key, $data)) {
				$value = &$data->{$key};
				if ($_ref = $object->{$key}) {
					self::setClassProps($value, $_ref);
				} else {
					$object->{$key} = $value ?? null;
				}
			}
		}
	}

	public static function &getSessionInstance(): HttpSession {
		return self::getSession()['INSTANCE'];
	}

	public static function &getSession(): iterable {
		$projectName = Project::getName();
		if (isset($_SESSION[$projectName])) {
			return $_SESSION[$projectName];
		}
		
		$session = [
			'INSTANCE' => new HttpSession()
		];
		
		$_SESSION[$projectName] = &$session;
		
		return $session;
	}
}
