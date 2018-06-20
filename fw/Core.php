<?php
namespace fw;

use fw\http\HttpSession;
use fw\router\Router;
if (! defined('LIBXML_HTML_NODEFDTD')) {
	define("LIBXML_HTML_NODEFDTD", 4);
	define("LIBXML_HTML_NOIMPLIED", 8192);
}

abstract class Core {

	private const PATH_SRC = 'src';

	public const PATH_BUILD = 'build';

	private const PATH_PROJECT_CONFIG = self::PATH_SRC . '/config.php';

	protected static $pageCodeResponse = array();

	protected static $CONTEXT_PATH;

	private static $template;

	static function init(): void {
		spl_autoload_register(function ($class_name) {
			include str_replace('\\', '/', $class_name) . '.php';
		});
		
		if (is_file(self::PATH_PROJECT_CONFIG)) {
			include self::PATH_PROJECT_CONFIG;
		}
		
		if (! is_file($pathRouter = self::PATH_SRC . '/router.php')) {
			throw new \Exception('Não foi encontrado o arquivo de configuração de routeamento em: src/router.php');
		}
		
		if (isset($_REQUEST['url'])) {
			$APP_URL = $_REQUEST['url'];
			$APP_URL = substr($APP_URL, - 1) === '/' ? substr($APP_URL, 0, - 1) : $APP_URL;
		} else {
			$APP_URL = '/';
		}
		
		if (! is_dir(self::PATH_BUILD)) {
			mkdir(self::PATH_BUILD, 0777, true);
		}
		
		if (Project::getCheckModification()) {
			include $pathRouter;
		} else {
			$pathRouterSource = self::PATH_BUILD . '/router.src';
			
			$reflectionClass = new \ReflectionClass(Router::class);
			$propList = $reflectionClass->getProperty('list');
			$propList->setAccessible(true);
			
			if (! is_file($pathRouterSource)) {
				include $pathRouter;
				$list = $propList->getValue();
				file_put_contents($pathRouterSource, serialize($list));
			} else {
				$propList->setValue(unserialize(file_get_contents($pathRouterSource)));
			}
			
			$propList->setAccessible(false);
		}
		
		$config = Router::getConfig($APP_URL);
		
		if (! $config) {
			http_response_code(404);
			if ($pagePath = self::$pageCodeResponse[404] ?? null) {
				readfile($pagePath);
				exit();
			} else {
				exit('PAGE NOT FOUND');
			}
		}
		
		self::$CONTEXT_PATH = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])) . '/';
		
		$requestedMethod = $config['methodName'];
		$accessRule = $config['accessRule'];
		
		if ($accessRule && ! self::hasAccess($accessRule, $requestedMethod)) {
			http_response_code(401);
			if ($pagePath = self::$pageCodeResponse[401] ?? null) {
				readfile($pagePath);
				exit();
			} else {
				exit('Você não está autorizado a executar essa ação.');
			}
		}
		
		if ($controllerClass = $config['controllerClass']) {
			$session = &self::getSession();
			
			$controller = $session[$controllerClass] ?? null;
			
			if (! $controller) {
				$controller = new $controllerClass();
				if (! ($controller instanceof ComponentController)) {
					die('O controlador ' . $controllerClass . ' precisa extender a classe ComponentController.');
				}
				$session[$controllerClass] = $controller;
			}
			
			$reflectionClass = new \ReflectionClass($controllerClass);
			
			if ($reflectionClass->hasMethod($requestedMethod)) {
				$reflectionMethod = $reflectionClass->getMethod($requestedMethod);
				
				$methodResult;
				session_start();
				$params = $reflectionMethod->getParameters();
				
				if (count($params) > 0) {
					$list = Array();
					foreach ($params as $param) {
						$classType = $param->getType();
						$paramName = $param->getName();
						
						if ($classType && ! $classType->isBuiltin()) {
							$className = $classType->getName();
							$arg = new $className();
							self::setClassProps($_REQUEST[$paramName], $arg);
							
							$list[] = $arg;
						} elseif ($arg = ($_REQUEST[$paramName] ?? null)) {
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
		}
		
		/*
		 * $httpX = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? null;
		 * $IS_AJAX = $httpX && strtolower($httpX) === 'xmlhttprequest';
		 */
	}

	private static function hasAccess(Array $rules, String $methodName): bool {
		$user = self::getSessionInstance()->getUserPrincipal();
		if ($user === null) {
			return false;
		}
		
		if (count($rules) > 0) {
			$rule = $rules[$methodName] ?? null;
			if (! $rule) {
				$rule = $rules['*'] ?? null;
			}
			
			if ($rule && $rule !== '*') {
				$userRules = $user->getRules();
				if ($userRules) {
					foreach ($userRules as $v) {
						if (in_array($v, $rules)) {
							return true;
						}
					}
				}
				
				return false;
			}
		}
		
		return true;
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
		return $_SESSION[Project::getName()]['INSTANCE'];
	}

	public static function &getSession(): iterable {
		$projectName = Project::getName();
		if (isset($_SESSION[$projectName])) {
			return $_SESSION[$projectName];
		}
		
		$session = Array(
			'INSTANCE' => new HttpSession()
		);
		
		$_SESSION[$projectName] = &$session;
		
		return $session;
	}

	private static function delete_files($target): void {
		if (is_dir($target)) {
			$files = glob($target . '*', GLOB_MARK);
			
			foreach ($files as $file) {
				self::delete_files($file);
			}
			
			rmdir($target);
		} elseif (is_file($target)) {
			unlink($target);
		}
	}
}
