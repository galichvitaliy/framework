<?php namespace Mirage;
/**
 * Created by PhpStorm.
 * User: galych
 * Date: 22.12.14
 * Time: 17:22
 */


class App {

	static protected $container;

	static function setContainer(\Pimple\Container $container) {
		static::$container = $container;
	}

	static function set($id, $value) {
		static::$container[$id] = $value;
	}

	static function get($id) {
		return isset(static::$container[$id]) ? static::$container[$id] : false;
	}
}