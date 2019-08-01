<?php

namespace Arembi\Xfw\Core;

class Session {
	// The SessionCore's model (SessionCoreModel object)
	private static $model;

	private static $lifeTime;

	public static function init()
	{
		if(IS_LOCALHOST) {
			session_name('x0_sess_' . str_replace('.', '_', DOMAIN));
		}

		self::$lifeTime = get_cfg_var("session.gc_maxlifetime");

		// Setting up database session handler if necessary
		if (Config::_('sessionStorage') == 'database'){
			self::$model = new SessionModel();

			session_set_save_handler(
				array(__CLASS__, "_open"),
				array(__CLASS__, "_close"),
				array(__CLASS__, "_read"),
				array(__CLASS__, "_write"),
				array(__CLASS__, "_destroy"),
				array(__CLASS__, "_gc")
			);
		}

		// Start the session
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


	public static function _destroy($id)
	{
		return self::$model->destroy($id);
	}

}
