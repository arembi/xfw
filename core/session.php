<?php

namespace Arembi\Xfw\Core;

class Session {
	// The SessionCore's model (SessionCoreModel object)
	private static $model;

	private static $lifeTime;

	public static function init()
	{
		if (IS_LOCALHOST) {
			session_name('xfw_sess_' . str_replace('.', '_', DOMAIN));
		}

		self::$lifeTime = get_cfg_var("session.gc_maxlifetime");

		// Setting up database session handler if necessary
		if (Config::_('sessionStorage') == 'database') {
			self::$model = new SessionModel();

			session_set_save_handler(
				[__CLASS__, "_open"],
				[__CLASS__, "_close"],
				[__CLASS__, "_read"],
				[__CLASS__, "_write"],
				[__CLASS__, "_destroy"],
				[__CLASS__, "_gc"]
			);
		}
	}

	public static function start()
	{
		session_start();
	}


	public static function _open()
	{
		return self::$model ? true : false;
	}


	public static function _close()
	{
		return true;
	}


	public static function _read($id)
	{
		$data = self::$model->readData($id);
		return $data ?? '';
	}


	public static function _write($id, $data)
	{
		return self::$model->writeData($id, $data);
	}


	public static function _gc($max)
	{
		return self::$model->gc($max);
	}


	public static function _destroy()
	{
		return self::$model->destroy(session_id());
	}

	public static function reset()
	{
		$_SESSION = [];

		if (ini_get("session.use_cookies")) {
			$params = session_get_cookie_params();
			setcookie(session_name(), '', time() - 42000,
				$params["path"], $params["domain"],
				$params["secure"], $params["httponly"]
			);
		}
		
		session_destroy();
		
		session_start();
		
		$_SESSION['user'] = new User('_guest');

	}

}
