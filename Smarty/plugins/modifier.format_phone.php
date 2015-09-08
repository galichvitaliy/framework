<?php 
/* 
* Smarty plugin 
* ------------------------------------------------------------- 
* File: modifier.format_phone.php 
* Type: modifier 
* Name: format_phone 
* Purpose: format a 10-digit phone number 
* ------------------------------------------------------------- 
*/ 


function smarty_modifier_format_phone($number, $format="%s (%s) %s-%s-%s") { 
 	$original = $number; 
   $number = preg_replace("/\D/","",$number); 

   if (strlen($number) != 12 && strlen($number) != 11) return $original; 

   if(substr($number,0,1) == '7'){
	$res = '+'.sprintf( 
         $format, 
	  substr($number,0,1),
         substr($number,1,3),
	  substr($number,4,3), 
         substr($number,7,2), 
         substr($number,9,2) 
      ); 
   }
   elseif(substr($number,0,2) == '38'){
   	$res = '+'.sprintf( 
         $format, 
	  substr($number,0,2),
         substr($number,2,3),
	  substr($number,5,3), 
         substr($number,8,2), 
         substr($number,10,2) 
      ); 
   }
   else{
	return $original; 
   }

   return $res;
} 
?> 