<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:26
 */

namespace Mirage\Cache;

class MemcachedStore implements StoreInterface {

	protected $memcached;

	protected $prefix;

	public function __construct(\Memcached $memcached, $prefix = '')
	{
		$this->memcached = $memcached;
		$this->prefix = strlen($prefix) > 0 ? $prefix.':' : '';
	}

	public function get($key)
	{
		$value = $this->memcached->get($this->prefix.$key);

		if ($this->memcached->getResultCode() == 0) {

			return $value;
		}
	}

	public function put($key, $value, $minutes = 1)
	{
		$this->memcached->set($this->prefix.$key, $value, $minutes * 60);
	}

	public function forever($key, $value)
	{
		$this->put($key, $value, 0);
	}

	public function forget($key)
	{
		$this->memcached->delete($this->prefix.$key);
	}

	public function flush()
	{
		$this->memcached->flush();
	}

	public function getMemcached()
	{
		return $this->memcached;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}
}