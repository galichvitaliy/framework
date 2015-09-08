<?php
/**
 * Smarty plugin
 * 
 * @package Smarty
 * @subpackage PluginsModifier
 */

/**
 * Smarty ucfirstmodifier plugin
 * 
 * Type:     modifier<br>
 * Name:     ucfirst<br>
 * Purpose:  ucfirst first word in the string
 *
 * {@internal {$string|ucfirst} is the fastest option for MBString enabled systems }}
 *
 * @return string capitalized string
 * @author Sergey Slabak
 */
function smarty_modifier_ucfirst($string)
{
	return mb_strtoupper(mb_substr($string, 0, 1)) . mb_substr($string, 1);} 
?>  