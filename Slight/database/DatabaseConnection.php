<?php
namespace Slight\database;

final class DatabaseConnection {

	private static $configs = [];

	public static function getInstance(String $name = "default"): \PDO {
		try {
			$config = self::$configs[$name] ?? null;
			if($config === null) {
				throw new \Exception('it is not possible to acquire the \''.$name.'\' configuration to be able to connect to the database.');
			}
			
			return new \PDO($config['dbType'] . ':host=' . $config['host'] . ';dbname=' . $config['dbName'], $config['user'], $config['password'], $config['options']);
		} catch (\PDOException $e) {
			throw new \Exception('Could not connect database.');
		}
	}

	public static function register(String $name, String $dbType, String $host, String $dbName, String $user, String $password = null, Array $options = null): void {
		self::$configs[$name] = [
			'dbType' => $dbType,
			'host' => $host,
			'dbName' => $dbName,
			'user' => $user,
			'password' => $password,
			'options' => $options
		];
	}
}