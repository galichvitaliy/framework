<?php
/**
 * Created by PhpStorm.
 * User: galych
 * Date: 03.02.15
 * Time: 12:57
 */

namespace Mirage;

class HTTP {

	static $default_controller = "home";
	static $controller;
	static $default_action = "index";
	static $action;
	static $uri;
	static $cms = false;
	static $cms_action;
	static $page = 1;
	static $link = [];
	static $link_index = [];

	static public function setRouting() {
		$input_url = current(explode("?", strtolower($_SERVER['REQUEST_URI']))); //clean string from ?params
		self::$uri = trim($input_url, '/');

		$links = explode("/", self::$uri);
		self::$controller = isset($links[0]) ? $links[0] : self::$default_controller;
		array_shift($links);//removing controller name
		self::$action = isset($links[0]) ? $links[0] : self::$default_action;
		if(self::$cms) {
			self::$cms_action = isset($links[1]) ? $links[1] : false;
			array_shift($links);//removing cms action name
		}

		//check if last section is page, and store it in static vars
		if(preg_match('/page-(?P<page>\d+)/', end($links), $matches)) {
			self::$page = $matches['page'];
		}

		foreach ($links as $key => $value) {
			if($key%2 == 0) {
				self::$link[$value] = isset($links[$key+1]) ? $links[$key+1] : "";
			}
			self::$link_index[] = $links[$key];
		}
	}

	static public function get($id) {
		return isset($_GET[$id]) ? $_GET[$id] : false;
	}

	static public function post($id) {
		return isset($_POST[$id]) ? $_POST[$id] : false;
	}

	static public function postAll($decoded = false) {
		$vals = $_POST;
		if($decoded && $vals) {
			foreach ($vals as $key => $value) {
				$vals[$key] = urldecode($value);
			}
		}
		return !empty($vals) ? $vals : false;
	}

	static public function cookie($id) {
		return isset($_COOKIE[$id]) ? $_COOKIE[$id] : false;
	}

	static public function request($id) {
		return isset($_REQUEST [$id]) ? $_REQUEST [$id] : false;
	}

	static public function param($id) {
		return isset(self::$link[$id]) ? self::$link[$id] : false;
	}

	static public function index($id){
		return isset(self::$link_index[$id]) ? self::$link_index[$id] : null;
	}

	static public function redirect($path = false) {
		$path = !empty($path) ? $path : $_SERVER['REQUEST_URI'];
		header("Location: " . $path);
		flush();
		exit();
	}

	static public function val($name, $default = false) {
		if( isset($_POST[$name]) ){
			return $_POST[$name];
		} elseif( isset($_GET[$name]) ){
			return $_GET[$name];
		} elseif( isset($_SESSION[$name]) ){
			return $_SESSION[$name];
		} elseif( isset(self::$link[$name]) && self::$link[$name] )  {
			return self::$link[$name];
		} else {
			return $default;
		}
	}

	static public function getAllValsDecoded() {
		if( $_POST ){
			$vals = $_POST;
		} elseif( $_GET ){
			$vals = $_GET;
		} elseif (isset(self::$link)) {
			$vals = self::$link;
		} else {
			$vals = false;
		}
		if($vals) {
			foreach ($vals as $key => $value) {
				$vals[$key] = urldecode($value);
			}
		}
		return $vals;
	}

	static public function ip() {
		if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} else {
			$ip = $_SERVER['REMOTE_ADDR'];
		}
		return $ip;
	}

}