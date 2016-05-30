<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:27
 */

namespace Mirage\Cache;

interface StoreInterface {

	public function get($key);

	public function put($key, $value, $minutes = 1);

	public function forever($key, $value);

	public function forget($key);

	public function flush();

	public function getPrefix();
}