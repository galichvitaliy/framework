<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     outputfilter.debugbar.php
 * Type:     outputfilter
 * Name:     protect_email
 * Purpose:  debugbar
 * -------------------------------------------------------------
 */
use \Mirage\App;

function smarty_outputfilter_debugbar($output, Smarty_Internal_Template $template)
{
	$debugbar = App::get('debugbar');
	$debugbar->collect();
	$debugbarRenderer = $debugbar->getJavascriptRenderer()->setBaseUrl('/debugbar');

	$output = str_replace('</head>', $debugbarRenderer->renderHead().'</head>', $output);
	$output = str_replace('</body>', $debugbarRenderer->render().'</body>', $output);
	return $output;
}
?>