<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:25
 */

namespace Mirage\Cache;

class RedisStore implements StoreInterface {

	protected $redis;

	protected $prefix;

	protected $connection;

	public function __construct(Database $redis, $prefix = '', $connection = 'default')
	{
		$this->redis = $redis;
		$this->connection = $connection;
		$this->prefix = strlen($prefix) > 0 ? $prefix.':' : '';
	}

	public function keys($pattern)
	{
		// check if prefix includes lang glue symbol "_", and get keys of all languages
		if (strpos($this->prefix, "_") !== false) {
			list($prefix) = explode("_", $this->prefix);
			$keys = $this->connection()->keys($prefix."_??:".$pattern);
		} else {
			$keys = $this->connection()->keys($this->prefix.$pattern);
		}

		if ( ! empty($keys)) {
			return $keys;
		}
	}

	public function get($key)
	{
		if ( ! is_null($value = $this->connection()->get($this->prefix.$key))) {

			return is_numeric($value) ? $value : unserialize($value);
		}
	}

	public function put($key, $value, $minutes = 1)
	{
		$value = is_numeric($value) ? $value : serialize($value);

		$this->connection()->set($this->prefix.$key, $value);

		$this->connection()->expire($this->prefix.$key, $minutes * 60);
	}

	public function forever($key, $value)
	{
		$value = is_numeric($value) ? $value : serialize($value);

		$this->connection()->set($this->prefix.$key, $value);
	}

	public function forget($key)
	{
		$this->connection()->del($this->keys($key));
	}

	public function flush()
	{
		$this->connection()->flushdb();
	}

	public function connection()
	{
		return $this->redis->connection($this->connection);
	}

	public function setConnection($connection)
	{
		$this->connection = $connection;
	}

	public function getRedis()
	{
		return $this->redis;
	}

	public function getPrefix()
	{
		return $this->prefix;
	}

}