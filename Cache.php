<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:21
 */
namespace Mirage;

class Cache
{
	private static $_instance = null;

	private function __construct() {}
	protected function __clone() {}

	public static function getInstance()
	{
		if (is_null(self::$_instance)) {

			self::$_instance = new Cache\CacheManager();
		}

		return self::$_instance;
	}

	public static function __callStatic($method, $args)
	{
		$driver = 'create'.ucfirst(Config::get('cache.driver')).'Driver';

		return call_user_func_array(array(self::getInstance()->$driver(), $method), $args);
	}
}