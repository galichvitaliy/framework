<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:26
 */

namespace Mirage\Cache;

class MemcacheStore implements StoreInterface {

	protected $memcache;

	protected $prefix;

	public function __construct(\Memcache $memcache, $prefix = '')
	{
		$this->memcache = $memcache;
		$this->prefix = strlen($prefix) > 0 ? $prefix.':' : '';
	}

	public function get($key)
	{
		$value = $this->memcache->get($this->prefix.$key);

		if ($this->memcache->getResultCode() == 0) {

			return $value;
		}
	}

	public function put($key, $value, $minutes = 1)
	{
		$this->memcache->set($this->prefix.$key, $value, $minutes * 60);
	}

	public function forever($key, $value)
	{
		$this->put($key, $value, 0);
	}

	public function forget($key)
	{
		$this->memcache->delete($this->prefix.$key);
	}

	public function flush()
	{
		$this->memcache->flush();
	}

	public function getMemcache()
	{
		return $this->memcache;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}
}