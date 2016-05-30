<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:23
 */

namespace Mirage\Cache;

class Repository
{
	protected $store;

	protected $default = 60;

	public function __construct(StoreInterface $store)
	{
		$this->store = $store;
	}

	public function has($key)
	{
		return ! is_null($this->get($key));
	}

	public function get($key, $default = null)
	{
		$value = $this->store->get($key);

		return ! is_null($value) ? $value : ($default instanceof \Closure ? $default() : $default);
	}

	public function put($key, $value, $minutes = false)
	{
		$minutes = $minutes ?: $this->default;

		$this->store->put($key, $value, $minutes);
	}

	public function pull($key, $default = null)
	{
		$value = $this->get($key, $default);

		$this->forget($key);

		return $value;
	}

	public function add($key, $value, $minutes = false)
	{
		if (is_null($this->get($key))) {

			$minutes = $minutes ?: $this->default;

			$this->put($key, $value, $minutes); return true;
		}

		return false;
	}

	public function remember($key, $minutes = false, \Closure $callback)
	{
		if ( ! is_null($value = $this->get($key))) {

			return $value;
		}

		$minutes = $minutes ?: $this->default;

		$this->put($key, $value = $callback(), $minutes);

		return $value;
	}

	public function rememberForever($key, \Closure $callback)
	{
		if ( ! is_null($value = $this->get($key))) {

			return $value;
		}

		$this->forever($key, $value = $callback());

		return $value;
	}

	public function getDefaultCacheTime()
	{
		return $this->default;
	}

	public function setDefaultCacheTime($minutes)
	{
		$this->default = $minutes;
	}

	public function getStore()
	{
		return $this->store;
	}

	public function __call($method, $parameters)
	{
		return call_user_func_array(array($this->store, $method), $parameters);
	}
}