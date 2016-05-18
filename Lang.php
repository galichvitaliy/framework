<?php
/**
 * Created by PhpStorm.
 * User: galych
 * Date: 06.01.16
 * Time: 13:29
 */

namespace Mirage;


class Lang {

	static public function get($string, array $replace = array(), $override_lang = false)
	{

		$lang = $override_lang ? $override_lang : App::get('lang');
		list($file, $name) = explode(".", $string);

		$path = (App::get('lang_path') ? App::get('lang_path') : (App::get('root_dir'). "/template/".App::get('layout')."/lang/"))."{$lang}/{$file}.inc";

		if(!empty($file) && file_exists($path)) {
			$lines = require($path);
			$line = !empty($lines[$name]) ? $lines[$name] : "+";

			foreach ($replace as $key => $value) {
				$line = str_replace(':'.$key, $value, $line);
			}

			return $line;
		}

		return '-';

	}

}