<?php
/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 30.05.2016
 * Time: 15:24
 */

namespace Mirage\Cache;

class Database {

	protected $clients;

	public function __construct(array $servers = array())
	{
		if (isset($servers['cluster']) && $servers['cluster']) {

			$this->clients = $this->createAggregateClient($servers);
		} else {

			$this->clients = $this->createSingleClients($servers);
		}
	}

	protected function createAggregateClient(array $servers)
	{
		unset($servers['cluster']);

		return array('default' => new \Predis\Client(array_values($servers)));
	}

	protected function createSingleClients(array $servers)
	{
		$clients = array();

		foreach ($servers as $key => $server) {

			$clients[$key] = new \Predis\Client($server);
		}

		return $clients;
	}

	public function connection($name = 'default')
	{
		return $this->clients[$name ?: 'default'];
	}

	public function command($method, array $parameters = array())
	{
		return call_user_func_array(array($this->clients['default'], $method), $parameters);
	}

	public function __call($method, $parameters)
	{
		return $this->command($method, $parameters);
	}

}