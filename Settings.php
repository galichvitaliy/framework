<?php
/**
 * Created by PhpStorm.
 * User: sergey-sla
 * Date: 06.08.2015
 * Time: 17:28
 */

namespace Mirage;


class Settings {

	static function group($name = false) {
		//if(!$group = $this->cache->load('options_group_'.$name)) {
		$group = DB::getAssoc("SELECT `_key` id, `value` FROM `options` o, options_groups og WHERE og.alias=:alias AND o.group=og.id ORDER BY o.`order`", [':alias'=>$name]);
		//$this->cache->save($group, 'options_group_'.$name, 7200);
		//}
		return !empty($group) ? $group : false;
	}

	static function key($name, $group = "") {
		if(!empty($name)) {
			//if(!$key = $this->cache->load("options_key_".$group."_".$name)) {
			$group = $group ? self::group2Id($group) : 0;
			$key = DB::getCell("SELECT `value` FROM `options` WHERE `_key`=? AND `group`=?", [$name, $group]);
			//$this->cache->save($key, "options_key_".$group."_".$name, 7200);
			//}
			return isset($key) ? $key : false;
		}
		return false;
	}

	static function group2Id($group = false) {
		if($group) {
			//if(!$id = $this->cache->load("options_group2id_$group")) {
			$id = DB::getCell("SELECT id FROM options_groups WHERE alias=:alias", [':alias'=>$group]);
			//$this->cache->save($id, "options_group2id_$group", 7200);
			//}
			return $id ? $id : 0;
		}
		return 0;
	}

}