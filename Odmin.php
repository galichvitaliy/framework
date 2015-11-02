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
			} elseif($this->cms_action == "clone" && $this->getVal("clone")) {
				$this->simpleClone($this->entity, $this->getVal("clone"));
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
	function getVal($name, $default = false) {
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
	function getAllValsDecoded() {
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

	function simpleList() {

		if(!empty($this->entity['list']['sql'])) {
			$sql = $this->entity['list']['sql'];
		} else {
			//build query

			//$sql = "SELECT * FROM {$list['table']} ORDER BY {$list['primary_id']} DESC";
			$sql = "SELECT ";
			$join = "";

			foreach ($this->entity['list']['columns'] as $key => $value) {
				//if(Auth::hasRole("{$this->entity['name']}.view.{$key}")) {
				if(!empty($value['from']) && !empty($value['field'])) {
					$sql .= $value['from'].$key.".`".$value['field']."` as `$key`, ";
					$jid = !empty($value['pid']) ? $value['pid'] : "id";
					$join .= "LEFT JOIN {$value['from']} {$value['from']}{$key} ON {$value['from']}{$key}.`$jid` = {$this->entity['list']['table']}.`$key` ";
				} else {
					$sql .= $this->entity['list']['table'].".`".$key."`, ";
				}
				//}
			}
			//Check if is_active button is in config, and add this field for status
			if(!empty($this->entity['list']['actions']) && in_array("is_active", $this->entity['list']['actions'])) {
				$sql .= $this->entity['list']['table'].".is_active, ";
			}

			//If table primary id is not selected, add it manually
			if(empty($this->entity['list']['columns']['id'])) {
				$sql .= "{$this->entity['list']['table']}.`{$this->entity['list']['primary_id']}`, ";
			}

			$sql = substr($sql, 0, -2);
			$sql .= " FROM {$this->entity['list']['table']} $join WHERE 1 ORDER BY {$this->entity['list']['table']}.".(!empty($this->entity['list']['order_by']) ? $this->entity['list']['order_by'] : $this->entity['list']['primary_id']." DESC");

		}

		if(!empty($this->entity['list']['filter'])) {
			$sql = $this->model->{$this->entity['list']['filter']}($sql);

			//load filter vars, if available and pass it to template
			if(method_exists($this->model, 'cmsFilterData')) {
				$filer_arr = $this->model->cmsFilterData();
				if(is_array($filer_arr)) {
					foreach ($filer_arr as $f_key => $f_val) {
						$this->tpl->assign($f_key, $f_val);
					}
				}
			}
		}

		if(!empty($this->entity['list']['pagination'])) {
			$sql = Helper::paginator($sql, $this->entity['list']['pagination']);
		}

		if(!empty($this->entity['list']['load'])) {
			$dbr = $this->model->{$this->entity['list']['load']}();
		} else {
			//$dbr = $this->db->select($sql);
			$dbr = DB::getAll($sql);
		}

		if($dbr) {
			foreach ($dbr as $row_key => $row) {
				foreach ($row as $key => $value) {
					if(!empty($this->entity['list']['columns'][$key]['callback'])) {
						$callback = $this->entity['list']['columns'][$key]['callback'];
						$dbr[$row_key][$key] = $this->$callback($value);
					} elseif(!empty($this->entity['list']['columns'][$key]['option'])) {
						$dbr[$row_key][$key] = !empty($this->entity['list']['columns'][$key]['group']) ? current(Settings::key($value, $this->entity['list']['columns'][$key]['group']))['value'] : current(Settings::key($value))['value'];
					}
				}
			}
			$this->tpl->assign('rows', $dbr);
		}

		$this->tpl->assign('title', $this->entity['title']);
		$this->tpl->assign('entity', $this->entity);

		if(file_exists(App::get('root_dir')."/template/".App::get('layout')."/tpl/filter/".$this->entity['name'].".tpl")) {

			/* remove this, or refactor */
			/*if ($_POST && empty($_POST['fetch'])) {
				$query_string = "";
				foreach ($_POST as $p_key => $p_value) {
					if(!empty($p_value)) {
						$query_string .= "/$p_key/".urlencode($p_value);
					}
				}

				//$search = $this->getVal('query');
				//$search_search = $search ? "/query/".urlencode($search) : "";

				$this->redirect_to("/{$this->controller}/".$this->entity['name'].$query_string);
			}*/
			if(!empty($_GET)) {
				$this->tpl->assign('filter', $_GET);
			}

			$this->tpl->assign('filter_tpl', $this->entity['name']);
		}

		if(!empty($_POST['fetch'])) {
			$html = $this->tpl->fetch("list.tpl");
			echo json_encode($html);
		} else {
			$this->tpl->display("extends:index.tpl|list.tpl");
		}

	}


	function simpleForm($id = false) {

		$act = $id ? "edit" : "add";
		//$source_array = array("select", "select_dual", "radio");

		//abrakadabra, this part is for look for add/edit array from other section
		$fields = (!empty($this->entity[$act]['fields']) && is_array($this->entity[$act]['fields']))
			? $this->entity[$act]['fields']
			: ( !empty($this->entity[$act]['fields']) && is_array($this->entity[$this->entity[$act]['fields']]['fields'])
				? $this->entity[$this->entity[$act]['fields']]['fields']
				: false
			);
		$db_fields = $this->prepareFields($fields);

		//Custom data loader
		if( !empty($this->entity[$act]['load']) ) {
			$data = $this->model->{$this->entity[$act]['load']}($id);
			$data['id'] = $id;
			if(!$id && !empty($this->entity['hash'])) {
				$hash = Helper::uniqHash($this->entity['table']);
				$data['hash'] = $hash;
			}
			$this->tpl->assign('item', $data);
		} elseif($id) {
			$data = $this->loadSimpleData($db_fields, $id);
			foreach ($data as $key => $value) {
				if(!empty($this->entity['add']['fields'][$key]['type']) && $this->entity['add']['fields'][$key]['type']=="editor") {
					$data[$key] = htmlspecialchars($value);
				}
				if(!empty($this->entity['add']['fields'][$key]['multi']) && empty($this->entity['add']['fields'][$key]['handler'])) {
					$data[$key] = unserialize($value);
				}
			}
			$data['id'] = $id;
			$this->tpl->assign('item', $data);
		} else {
			if(!empty($this->entity['table']) && !empty($this->entity['hash'])) {
				$hash = Helper::uniqHash($this->entity['table']);
				$this->tpl->assign('item', array('hash'=>$hash));
			}
		}

		if(file_exists(App::get('root_dir')."/template/".App::get('layout')."/tpl/form/".$this->entity['name'].".tpl")) {
			if(!empty($_GET['fetch'])) {
				$html = $this->tpl->fetch("form/{$this->entity['name']}.tpl");
				echo json_encode($html);
			} else {
				$this->tpl->assign('title', $this->entity[$act]['title']);
				$this->tpl->display("extends:index.tpl|form/{$this->entity['name']}.tpl");
			}
		} else {
			foreach ($db_fields as $key => $field) {
				if(!empty($field['source'])) {
					//if(in_array($field['type'], $source_array) && is_array($field['source'])) {
					if(is_array($field['source'])) {
						$fields[$key]['vals'] = $field['source'];
					} elseif ($field['source'] == "sql") {
						$vals_tmp = $dbr = DB::getAll($field['sql']);
						$vals = [];
						if($vals_tmp) {
							foreach ($vals_tmp as $val) {
								$vals[$val['id']] = $val['title'];
							}
						}
						$fields[$key]['vals'] = $vals;
					} elseif ($field['source'] == "option" && !empty($field['group'])) {
						$fields[$key]['vals'] = Settings::group($field['group']);
					} elseif ($field['source'] == "model") {
						$fields[$key]['vals'] = $this->model->{$field['method']}();
					}
					elseif($field['source'] == "enum") {
						$fields[$key]['vals']  = $this->get_enum_values($this->entity['table'], $key);
					}

					if(!empty($field['add_empty'])) {
						if(is_array($fields[$key]['vals'])) {
							$fields[$key]['vals'] = [''=>""] + $fields[$key]['vals'];
						} else {
							$fields[$key]['vals'] = [''=>""];
						}

					}
				}

			}

			$this->tpl->assign('title', $this->entity[$act]['title']);
			$this->tpl->assign('entity', $this->entity[$act]);
			$this->tpl->assign('fields', $fields);

			if(!empty($_GET['fetch'])) {
				$html = $this->tpl->fetch("g_form.tpl");
				echo json_encode($html);
				exit;
			} else {
				$this->tpl->display("extends:index.tpl|g_form.tpl");
			}
		}
	}

	function simpleDelete($entity, $id = false) {
		$st = false;
		if($id) {
			if(is_array($id)) {
				$ids = implode("', '", array_map("urldecode", array_map("intval", $id)));
			} else {
				$id = ($id);
				$ids = urldecode((int)$id);
			}
			if(DB::exec("DELETE FROM {$entity['table']} WHERE {$entity['primary_id']} IN ('$ids')")) {
				$st = true;
			}
		}
		echo json_encode($st);
	}

	function simpleClone($entity, $id = false) {
		$st = false;
		if($id) {
			$id = ($id);
			$ids = urldecode((int)$id);
			$bean = DB::load( $entity['table'], $ids );
			$duplicated = DB::duplicate( $bean );
			if($new_id = DB::store( $duplicated )) {
				$st = $new_id;
			}
		}
		echo json_encode($st);
	}

	function loadSimpleData($fields, $id) {

		$field = [];
		$model_fields = [];

		if(isset($this->entity['hash']) && $this->entity['hash'] == true) {
			$field[] = "hash";
		}

		foreach ($fields as $key => $opt) {

			if(!empty($this->entity['add']['fields'][$key]['handler'])) {
				$model_fields[] = $key;
				if(empty($this->entity['add']['fields'][$key]['skip_load'])) {
					continue;
				}
			}
			switch ($opt['type']) {

				case 'files':
				case 'gallery':
				case 'video':
				case 'clear':
				case 'title':
					break;

				case 'seo':
					$field[] = "seo_title";
					$field[] = "seo_keywords";
					$field[] = "seo_description";
					break;

				default:
					$field[] = $key;
					break;
			}
		}

		$res = DB::getRow("SELECT `".implode("`, `", $field)."` FROM {$this->entity['table']} WHERE {$this->entity['primary_id']} = '$id'");

		if(!empty($this->entity['ex_load']) && !empty($this->entity['model'])){
			$res = $this->model->{$this->entity['ex_load']}($id, $model_fields, $res);
		}

		return !empty($res) ? $res : false;
	}

	function img() {

		$key = HTTP::post('key');
		$fields = is_array($this->entity["add"]['fields']) ? $this->entity["add"]['fields'] : false;//wtf??
		$db_fields = $this->prepareFields($fields);

		if(!$key && !isset($db_fields[$key])) {
			return false;
		}

		$ent = $db_fields[$key];

		$path = App::get('public_dir')."/".$ent['path'].(HTTP::post('hash') ? HTTP::post('hash')."/" : "");
		Helper::checkDir($path);
		$tmp_path = App::get('runtime_dir')."/tmp/";
		Helper::checkDir($tmp_path);

		if(move_uploaded_file($_FILES['img']['tmp_name'], $tmp_path.$_FILES['img']['name'])) {
			$filename = Helper::recursiveFilename($path, strtolower(pathinfo($tmp_path.$_FILES['img']['name'], PATHINFO_FILENAME)), !empty($ent['ext']) ? $ent['ext'] : "jpg");

			if(!empty($ent['sizes'])) {
				$resize = new \Mirage\Image($tmp_path.$_FILES['img']['name'], !empty($ent['ext']) ? $ent['ext'] : "jpg");
				$resize->outputQuality = 90;

				foreach ($ent['sizes'] as $size) {
					$crop = (!empty($size['crop']) && $size['crop']) ? $size['crop'] : false;
					$fill = (!empty($size['fill']) && $size['fill']) ? $size['fill'] : false;
					$width = !empty($size['width']) ? $size['width'] : false;
					$height = !empty($size['height']) ? $size['height'] : false;
					$prefix = !empty($size['prefix']) ? $size['prefix']."_" : '';
					$wm = (!empty($size['watermark']) && $size['watermark']) ? $size['watermark'] : false;

					if($wm) {
						$resize->waterMark($tmp_path.$_FILES['img']['name'], App::get('public_dir').$wm);
					}

					if($crop) {
						$resize->centerResize($path.$prefix.$filename, $width, $height);
					} elseif($fill) {
						$resize->fillResize($path.$prefix.$filename, $width, $height);
					} else {
						if($width && !$height){
							$resize->widthRestriction($path.$prefix.$filename, $width);
						} elseif($height && !$width){
							$resize->heightRestriction($path.$prefix.$filename, $height);
						} else {
							$resize->limitBoxResize($path.$prefix.$filename, $width, $height);
						}
					}
				}
			} else {
				copy($tmp_path.$_FILES['img']['name'], $path.$filename);
			}

			unlink($tmp_path.$_FILES['img']['name']);

			echo json_encode(array(
				'name'	    => $filename,
				'path'	    => "/".$ent['path'].(HTTP::post('hash') ? HTTP::post('hash')."/" : ""),
				'th_name'	=> is_file($path."th_".$filename) ? "th_".$filename : $filename,
			));
		}
	}

	function imgLoad() {

		$id = HTTP::post('id');
		$path = "/".HTTP::post('path');
		$path .= HTTP::post('hash') ? HTTP::post('hash')."/" : '';
		$iarr = null;

		if($id) {
			$imgs = DB::getCol("SELECT img FROM {$this->entity['table']}_gallery WHERE {$this->entity['table']}_id=?", [$id]);
			if($imgs) {
				foreach ($imgs as $img) {
					$iarr[] = [
						'path'	    => $path,
						'name'	    => $img,
						'th_name'	=> "th_".$img,
					];
				}
				echo json_encode($iarr);
			}
		}
	}

	function imgDel() {

		$key = HTTP::post('key');
		$filename = HTTP::post('filename');
		$hash = HTTP::post('hash') ? HTTP::post('hash').'/' : '';
		$fields = is_array($this->entity["add"]['fields']) ? $this->entity["add"]['fields'] : false;
		$db_fields = $this->prepareFields($fields);

		if(!$key && !isset($db_fields[$key])) {
			return false;
		}

		$ent = $db_fields[$key];

		foreach ($ent['sizes'] as $size) {
			$prefix = !empty($size['prefix']) ? $size['prefix']."_" : '';
			if(is_file(App::get('public_dir')."/".$ent['path'].$hash.$prefix.$filename)) {
				unlink(App::get('public_dir')."/".$ent['path'].$hash.$prefix.$filename);
			}
		}

		echo json_encode(true);
	}

}