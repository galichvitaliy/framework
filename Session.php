<?php namespace Mirage;

/**
 * Created by PhpStorm.
 * User: Dell
 * Date: 18.05.2016
 * Time: 12:55
 */
class Session extends \SessionHandler
{

	protected $key, $name, $cookie;
	private $started;

	public function __construct($name = 'MY_SESSION', $cookie = [])
	{
		$this->name = $name;
		$this->cookie = $cookie;
		#$this->started = false;

		$this->cookie += [
			'lifetime' => 0,
			'path'     => ini_get('session.cookie_path'),
			'domain'   => ini_get('session.cookie_domain'),
			'secure'   => isset($_SERVER['HTTPS']),
			'httponly' => true
		];

		$this->setup();

		if (ini_get('session.auto_start')) {
			$this->start();
		}
	}

	protected function setup()
	{
		ini_set('session.use_cookies', 1);
		ini_set('session.use_only_cookies', 1);

		session_name($this->name);

		session_set_cookie_params(
			$this->cookie['lifetime'], $this->cookie['path'],
			$this->cookie['domain'], $this->cookie['secure'],
			$this->cookie['httponly']
		);

		#$this->isFingerprint();
		#$this->isExpired();
	}

	public function start($force_create = false)
	{

		if(isset($_COOKIE[$this->name]) || $force_create ) {
			if (session_id() === '') {
				if (session_start()) {
					$this->started = true;
					//var_dump(session_id());
					#\Helper::LogIt('session_jobs', session_id()."\t".session_status()."\t".$_SERVER['HTTP_USER_AGENT']."\t".$_SERVER['REMOTE_ADDR']);
					return true;
					//return mt_rand(0, 4) === 0 ? $this->regenerate(true) : true; // 1/5
				}
			}
		}
		return false;
	}

	//session()->get('key', 'default');
	public function get($key, $default = false)
	{
		$this->started || $this->start();
		if ( !is_string($key) ) {
			throw new \Exception('Session key must be string value');
		}

		return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
	}

	public function pull($key, $default = false)
	{
		$value = $this->get($key, $default);
		$this->forget($key);
		return $value;
	}

	public function all()
	{
		$this->started || $this->start();
		return $_SESSION;
	}

	public function has($key)
	{
		$this->started || $this->start();
		if ( !is_string($key) ) {
			throw new \Exception('Session key must be string value');
		}
		return isset($_SESSION[$key]);
	}

	public function set($key, $value = NULL)
	{
		$this->started || $this->start(true);
		if ( !is_string($key) ) {
			throw new \Exception('Session key must be string value');
		}
		$_SESSION[$key] = $value;
	}

	public function forget($key)
	{
		$this->started || $this->start();
		if(isset($_SESSION[$key])) {
			unset($_SESSION[$key]);
		}
	}

	public function flush()
	{
		$this->started || $this->start();
		$_SESSION = array();
		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
	}

	public function regenerate($delete_old_session = false)
	{
		return session_regenerate_id($delete_old_session);
	}

	public function flash($key)
	{
		$this->started || $this->start(true);
		if(isset($_SESSION['_flashBag'][$key])) {
			$value = $_SESSION['_flashBag'][$key];
			unset($_SESSION['_flashBag'][$key]);
			return $value;
		}
		return false;
	}

	public function setFlash($key, $value)
	{
		$this->started || $this->start(true);
		$_SESSION['_flashBag'][$key] = $value;
	}

	public function isExpired($ttl = 30)
	{
		$this->started || $this->start();

		$activity = isset($_SESSION['_last_activity'])
			? $_SESSION['_last_activity']
			: false;

		if ($activity !== false && time() - $activity > $ttl * 60) {
			return true;
		}
		$this->set('_last_activity', time());
		return false;
	}

	public function isFingerprint()
	{
		$this->started || $this->start();
		$hash = md5(
			(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') .
			(ip2long(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') & ip2long('255.255.0.0'))
		);

		if (isset($_SESSION['_fingerprint'])) {
			return $_SESSION['_fingerprint'] === $hash;
		}
		$this->set('_fingerprint', $hash);
		return true;
	}

	public function isValid($ttl = 30)
	{
		return ! $this->isExpired($ttl) && $this->isFingerprint();
	}

}