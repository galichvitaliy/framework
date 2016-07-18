<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:21
 */
namespace Mirage\Cache;

use Mirage\Config;

class CacheManager
{
	private $memcached = null;
	private $memcache = null;
	private $redis = null;
	private static $repository_instance = null;

	public function createMemcachedDriver()
	{
		if (is_null($this->memcached)) {

			$this->memcached = new \Memcached;

			$servers = Config::get('cache.memcached') ?: [];

			foreach ($servers as $server) {

				$this->memcached->addServer( $server['host'], $server['port'], $server['weight'] );
			}

			if ($this->memcached->getVersion() === false) {

				throw new \RuntimeException("Could not establish Memcached connection.");
			}
		}

		return $this->repository(new MemcachedStore($this->memcached, $this->getPrefix()));
	}

	public function createMemcacheDriver()
	{
		if (is_null($this->memcache)) {

			$this->memcache = new \Memcache;

			$servers = Config::get('cache.memcache') ?: [];

			foreach ($servers as $server) {

				$this->memcache->addServer( $server['host'], $server['port'], $server['weight'] );
			}

			if ($this->memcache->getVersion() === false) {

				throw new \RuntimeException("Could not establish Memcache connection.");
			}
		}

		return $this->repository(new MemcacheStore($this->memcache, $this->getPrefix()));
	}

	public function createRedisDriver()
	{
		if (is_null($this->redis)) {

			$this->redis = new Database(Config::get('cache.redis'));
		}

		return $this->repository(new RedisStore($this->redis, $this->getPrefix()));
	}

	public function getPrefix()
	{
		return Config::get('cache.prefix');
	}

	public function getDefaultDriver()
	{
		return Config::get('cache.driver');
	}

	public function repository(StoreInterface $store)
	{
		if (is_null(self::$repository_instance)) {

			self::$repository_instance = new Repository($store);
		}

		return self::$repository_instance;
	}

}