<?php
/**
 * Smarty plugin
 * @package Smarty
 */

/**
 * Smarty Auth::has_role modifier plugin
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

function smarty_modifier_has_role($string, array $replace = array(), $override_lang = false)
{
	return \Mirage\Auth::hasRole($string);
}