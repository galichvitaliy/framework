<?php namespace Mirage;

class Auth {

	static $user = array();
	static $cookie = false;

	/**
	 *
	 */
	static function attempt($param = [], $remember = false)
	{
		if(self::validate($param)) {
			self::createSession(self::$user['id'], $remember); //auth
			/*$cookie = [
				'login'  => self::$user['login'],
				'photo'  => self::$user['photo'],
				'email'  => self::$user['email']
			];
			setcookie('lli', base64_encode(serialize($cookie)), time()+1209600, "/");*/
			$session = App::get('session');
			$session->set('user', self::$user);
			return true;
		}
		return false;
	}

	/**
	 * Determining If A User Is Authenticated
	 */
	static function check()
	{
		$session = App::get('session');
		return $session->get('auth');
	}

	/**
	 * Get user data
	 */
	static function data($key = false)
	{
		$session = App::get('session');
		$data = $session->get('user');
		return $key ? (isset($data[$key]) ? $data[$key] : false) : $data;
	}

	/**
	 * Validating User Credentials Without Login
	 */
	static function validate($param = [], $options = [])
	{

		//default fields is email and password, but may be overridden via options array
		$password = isset($options['password']) ? $options['password'] : 'password';

		$check_array = [];
		$check_sql = "1";
		foreach ($param as $key => $item) {
			if ($key != $password) {
				$check_array[":$key"] = $item;
				$check_sql .= " AND $key = :$key ";
			}
		}

		$user = DB::findOne('users', $check_sql, $check_array);

		if ($user && password_verify($param[$password], $user->$password)) {
			if($user->group > 0) {
				$user['rights'] = self::loadRights((int)$user->group);
			}
			self::$user = $user;
			return true;
		}

		return false;
	}

	/**
	 * Validating User Credentials Without Login
	 */
	static private function createSession($uid, $remember = false)
	{
		$session = App::get('session');
		if($remember) {
			//$cookie = $uid . '|' . md5($uid . MD5_SOLT . $_SERVER['HTTP_USER_AGENT'] );
			//setcookie(AUTH_ID, $cookie, time()+10800, "/");
		}
		$session->set('auth', $uid);

	}

	/**
	 * Determining If User Authed Via Remember
	 */
	static function viaRemember()
	{
		return self::$cookie;
	}

	/**
	 * Log a user into the application by their ID
	 */
	static function loginUsingId($id)
	{
		$session = App::get('session');
		$user = DB::load('users', $id);
		if($user->group > 0) {
			$user['rights'] = self::loadRights((int)$user->group);
		}
		self::$user = $user;
		$session->set('user', self::$user);
		self::createSession($id);
	}

	/**
	 *
	 */
	static function logout()
	{
		$session = App::get('session');
		$session->flush();
	}

	/**
	 *
	 */
	static function hasRole($id = false)
	{
		if(!self::check()) {
			return false;
		}
		$rights = self::data('rights');
		if(is_array($rights) && in_array($id, $rights)) {
			return true;
		}
		return false;
	}

	/**
	 *
	 */
	static function isAdmin()
	{
		return self::hasRole('general.admin_access');
	}

	/**
	 * Retrieve the authenticated user's ID
	 */
	static function id()
	{
		return self::data('id');
	}

	/**
	 *
	 */
	static private function loadRights($group_id = false)
	{
		$rights = DB::getCell('SELECT rules FROM users_groups WHERE id = ?', [$group_id]);
		return $rights ? unserialize($rights) : false;
	}

}