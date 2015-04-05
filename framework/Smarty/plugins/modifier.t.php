<?php
/**
 * Smarty plugin
 * @package Smarty
 */

/**
 * Smarty translate modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     translate<br>
 * Purpose:  simple translate
 * 
 * @author Galych Vitaliy <galych.vitaliy@gmail.com> 
 * @param string $string  input string
 * @param string/array $params params to replace in text
 * @return string 
 */
use \Mirage\App;

function smarty_modifier_t($string, array $replace = array(), $override_lang = false)
{

	$lang = $override_lang ? $override_lang : App::get('lang');
	list($file, $name) = explode(".", $string);

	$path = App::get('root_dir')."/template/".App::get('layout')."/lang/$lang/".$file.".inc";

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

?>