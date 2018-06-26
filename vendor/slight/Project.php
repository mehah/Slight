<?php
namespace fw;

final class Project extends Core {

	private static $name = 'SLIGHT';

	private static $chatset = 'UTF-8';

	private static $cacheRouter = false;

	private static $CONTEXT_PATH = null;

	public static function getName(): string {
		return Project::$name;
	}

	public static function getChatset(): string {
		return Project::$chatset;
	}

	public static function cacheRouter(bool $cache): void {
		Project::$cacheRouter = $cache;
	}

	public static function cachedRouter(): bool {
		return Project::$cacheRouter;
	}

	public static function setName(string $name): void {
		Project::$name = $name;
	}

	public static function setChatset(string $chatset): void {
		Project::$chatset = $chatset;
	}

	public static function isLocalHost() {
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
}