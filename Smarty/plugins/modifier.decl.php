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
    if(!is_array($expr)) $expr = array_filter(explode(' ', $expr));
    if(empty($expr[2])) $expr[2]=$expr[1];
    $i=preg_replace('/[^0-9]+/s','',$digit)%100; //intval не всегда корректно работает
    if($onlyword) $digit='';
    if($i>=5 && $i<=20) $res=$digit.' '.$expr[2];
    else {
        $i%=10;
        if($i==1) $res=$digit.' '.$expr[0];
        elseif($i>=2 && $i<=4) $res=$digit.' '.$expr[1];
        else $res=$digit.' '.$expr[2];
    }
    return trim($res);
}

?>