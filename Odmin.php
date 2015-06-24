<?php
/**
 * Created by PhpStorm.
 * User: Forcer
 * Date: 20.06.2015
 * Time: 20:42
 */

namespace Mirage;


class Odmin extends Controller {

	protected $ext, $entity, $link, $action, $cms_action, $controller, $model;

	function __construct() {

		HTTP::$cms = true;
		HTTP::$default_controller = 'odmin';

		$this->setRouting();
		App::set('layout', $this->controller);
		parent::__construct();

		$this->tpl->template_dir	= App::get('root_dir')."/template/".App::get('layout')."/tpl/";
		$this->tpl->assign('bmd', '/'.App::get('layout'));
		$this->tpl->assign('controller', $this->controller);
		$this->tpl->assign('action', $this->action);

	}

	function run() {

		//$this->tpl->display('index.tpl');

		if(!Auth::check() && $this->action != "login") {
			$this->redirect_to("/{$this->controller}/login");
		}

		if(!Auth::isAdmin() && $this->action != "login") {
			$this->redirect_to("/");
		}

		if(!empty($_COOKIE['hide_left_menu'])) {
			$this->tpl->assign('hide_left_menu', $_COOKIE['hide_left_menu']);
		}

		$this->log();

		if( file_exists(App::get('app_dir')."/cms/".$this->action.".inc") ) {
			$this->entity = $this->securityCheck(include(App::get('app_dir')."/cms/".$this->action.".inc"));
			//$this->entity = include(App::get('app_dir')."/cms/".$this->action.".inc");

			if (!empty($this->entity['model']) && class_exists($this->entity['model'])) {
				$this->model = new $this->entity['model']();
			}

			if($this->cms_action == "add") {
				if(!empty($_POST)) {
					if (!empty($this->entity['edit']['method'])) {
						$return_id = $this->model->{$this->entity['edit']['method']}($_POST);
						if($return_id) {
							echo json_encode(array('id' => $return_id));
						} else {
							echo json_encode(array('error' => "Error ocured"));
						}
					} else {
						$this->saveForm($this->entity);
					}
				} else {
					$this->simpleForm();
				}
			} elseif($this->cms_action == "edit") {
				$this->simpleForm($this->getVal("edit"));
			} elseif($this->cms_action == "delete" && $this->getVal("delete")) {

				if (!empty($this->entity['remove']['method'])) {
					$return_st = $this->model->{$this->entity['remove']['method']}($this->getVal("delete") == "mass" ? $this->getVal("item") : $this->getVal("delete"));

					if($return_st) {
						echo json_encode($return_st);
					} else {
						echo json_encode("Error ocured");
					}
				} else {
					$this->simpleDelete($this->entity, $this->getVal("delete") == "mass" ? $this->getVal("item") : $this->getVal("delete"));
				}
			} elseif ($this->cms_action == "ext") {
				$method = $this->getVal("ext");
				$this->model->$method($this->entity);

			} elseif ( $this->cms_action == "act" && method_exists($this, $this->getVal("act")) ) {
				$this->{$this->getVal("act")}();
			} else {
				if(empty($this->entity['list']) && !empty($this->entity['add'])) {
					$this->simpleForm();
				} else {
					$this->simpleList();
				}
			}
		} elseif( method_exists($this, $this->action ) ) {
			$this->{$this->action}();
		} else {
			$this->index();
		}
	}

	function setRouting() {
		$input_url = current(explode("?", strtolower($_SERVER['REQUEST_URI']))); //clean string from ?params
		$input_url = trim($input_url, '/');

		$links = explode("/", $input_url);
		$this->controller = isset($links[0]) ? $links[0] : "odmin";
		array_shift($links);//removing controller name
		$this->action = isset($links[0]) ? $links[0] : "index";
		$this->cms_action = isset($links[1]) ? $links[1] : false;
		array_shift($links);//removing cms action name

		$link = array();
		foreach ($links as $key => $value) {
			if($key%2 == 0) {
				$link[$value] = isset($links[$key+1]) ? $links[$key+1] : "";
			}
		}
		$this->link = $link;
	}

	//TODO: should be replaced with HTTP methods
	function getVal($name, $default = false)
	{
		if (isset($_POST[$name]) && $_POST[$name]) {
			return $_POST[$name];
		} elseif (isset($_GET[$name]) && $_GET[$name]) {
			return $_GET[$name];
		} elseif (isset($_SESSION[$name]) && $_SESSION[$name]) {
			return $_SESSION[$name];
		} elseif (isset($this->link[$name]) && $this->link[$name]) {
			return $this->link[$name];
		} else {
			return $default;
		}
	}

	//TODO: should be replaced with HTTP methods
	function getAllValsDecoded()
	{
		if ($_POST) {
			$vals = $_POST;
		} elseif ($_GET) {
			$vals = $_GET;
		} elseif (isset($this->link)) {
			$vals = $this->link;
		} else {
			$vals = false;
		}
		if ($vals) {
			foreach ($vals as $key => $value) {
				$vals[$key] = urldecode($value);
			}
		}
		return $vals;
	}

}