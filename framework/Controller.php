<?php namespace Mirage;

class Controller {

	protected $tpl;

	function __construct() {

		HTTP::setRouting();

		$this->tpl = App::get('view');
		$this->tpl->assign('bmd', '/'.App::get('layout'));
		$this->tpl->assign('site_url', Config::get('web.base_http'));
		$this->tpl->assign('controller', strtolower(get_called_class()));
		if(Config::get('web.langs')) {
			$this->tpl->assign('lang', App::get('lang'));//curent application language
			$this->tpl->assign('d_lang', Config::get('web.lang'));//default application language
		}

		if(Auth::check()) {
			$this->tpl->assign('logged_in', true);
			$this->tpl->assign('user', Auth::data());//curent application language
		} else {
			if(!empty($_COOKIE['lli'])) {
				$this->tpl->assign('last_visit', unserialize(base64_decode($_COOKIE['lli'])));
			}
		}

		if(Config::get('web.dev')) {
			if(!empty($_COOKIE['tmp_menu_hide'])) {
				$this->tpl->assign('tmp_menu_hide', $_COOKIE['tmp_menu_hide']);
			}
		}
		$this->init();
	}

	public function init() {}

}