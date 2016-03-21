<?php
/**
 * Smarty plugin
 * @package Smarty
 */

/**
 * Smarty declension modifier plugin
 * 
 * Type:     modifier<br>
 * Name:     declension<br>
 * Purpose:  simple declension
 * 
 * @author Sergey Sla <ahtixpect@gmail.com>
 * @param integer $digit integer number to decl
 * @param array $expr array of words
 * @param boolean $onlyword if true return only word without number, default false
 * @return string
 */

function smarty_modifier_decl($digit,$expr,$onlyword=false)
{
    return \Mirage\Helper::declension($digit, $expr, $onlyword);
}

?>