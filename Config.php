<?php namespace Mirage;

class Config {

	static $override = array();

	/**
	 * Get parameter from config file, using "dot" style, ex: web.db_name(/app/config/web.php, param "db_name")
	 *
	 * @param string $id Allowed db.user, web.path, etc.
	 * @return string
	 */
	static function get($id) {

		if(empty($id)) {
			return false;
		}

		list($file, $param) = explode(".", $id, 2);
		if(file_exists(App::get('app_dir')."/config/".$file.".php")) {
			$params = require(App::get('app_dir')."/config/".$file.".php");
			$value = !empty($params[$param]) ? $params[$param] : false;

			return $value;
		}

		return false;

	}

	/**
	 * Overrides given configuration in run-time only, will not actually affect config file
	 *
	 * @param string $id Allowed methods, | delimited
	 * @param string $value A route pattern such as /about/system
	 * @return string|object
	 */
	static function set($id, $value) {

	}

}