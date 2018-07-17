<?php
namespace Slight;

final class Config extends Core {

	private static $projectName = 'SLIGHT';

	private static $chatset = 'UTF-8';

	private static $cacheRouter = false;

	private static $CONTEXT_PATH = null;

	private static $statefullClass = null;

	private static $attributes;

	private static $defaultAuthClass;

	public static function getProjectName(): string {
		return Config::$projectName;
	}

	public static function getChatset(): string {
		return Config::$chatset;
	}

	public static function cacheRouter(bool $cache): void {
		Config::$cacheRouter = $cache;
	}

	public static function cachedRouter(): bool {
		return Config::$cacheRouter;
	}

	public static function setProjectName(string $name): void {
		Config::$projectName = $name;
	}

	public static function setChatset(string $chatset): void {
		Config::$chatset = $chatset;
	}

	public static function isLocalHost(): bool {
		$whitelist = [
			'127.0.0.1',
			'::1'
		];
		
		return in_array($_SERVER['REMOTE_ADDR'], $whitelist);
	}

	public static function getContextPath(): String {
		if (! self::$CONTEXT_PATH) {
			self::$CONTEXT_PATH = str_replace('\\', '/', dirname($_SERVER['PHP_SELF'])) . '/';
		}
		
		return self::$CONTEXT_PATH;
	}

	public static function setStatefulClass(string ...$class): void {
		self::$statefullClass = $class;
	}

	public static function isStatefulClass(string $class): bool {
		return self::$statefullClass !== null && in_array($class, self::$statefullClass);
	}

	public static function setAttribute(string $name, $value): void {
		self::$attributes[$name] = $value;
	}

	public static function getAttribute(string $name) {
		return self::$attributes[$name];
	}

	public static function getDefaultAuthClass(): string {
		return self::$defaultAuthClass;
	}

	public static function setDefaultAuthClass(string $defaultAuthClass): void {
		self::$defaultAuthClass = $defaultAuthClass;
	}
}