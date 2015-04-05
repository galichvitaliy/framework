<?php
/**
 * Created by PhpStorm.
 * User: Виталий
 * Date: 11.01.2015
 * Time: 19:41
 */

namespace Mirage;

class Model {

	public $validator;
	public $rules = array();
	public $filters = array();

	public function __construct() {
		$this->validator = new Validator();
		$this->init();
	}

	public function init() {

	}

	public function is_valid($data) {
		return $this->validator->validate($data, $this->rules) === TRUE ? 1 : false;
	}

	public function filter($data) {
		return $this->validator->filter($data, $this->filters);
	}

} 