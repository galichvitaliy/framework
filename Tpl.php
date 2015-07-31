<?php namespace Mirage;

class Tpl {

	public $smarty;

	function __construct($layout = "default") {
		$this->init($layout);
	}

	private function init($layout) {

		$smarty = new \Smarty();

		$smarty->setTemplateDir(App::get('root_dir')."/template/$layout/tpl/");
		//$smarty->template_dir	= App::get('root_dir')."/template/$layout/tpl/";
		$smarty->compile_dir	= App::get('runtime_dir')."/smarty";
		$smarty->cache_dir      = App::get('runtime_dir')."/smarty_cache";
		$smarty->config_dir     = App::get('runtime_dir')."/smarty_configs";
		$smarty->error_reporting	=  E_ALL & ~E_NOTICE;
		$smarty->inheritance_merge_compiled_includes = false;
		if( Config::get('web.dev') ) {
			$smarty->force_compile = true;
			$smarty->assign("dev", true);
		} else {
			$smarty->compile_check = false;
		}

		$smarty->addPluginsDir(__DIR__.'/Smarty/plugins');

		$my_security_policy = new \Smarty_Security($smarty);
		$my_security_policy->php_modifiers = array();
		$my_security_policy->php_functions = array('is_array','time','mb_strtolower');

		$smarty->enableSecurity($my_security_policy);

		$this->smarty = $smarty;
	}

}