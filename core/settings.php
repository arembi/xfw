<?php

//~ The settings are the configuration parameters read from the database
//~ They dont mix with the config class items

namespace Arembi\Xfw\Core;

class Settings {
	private static $settings;

	private static $model;


	public static function init()
	{
		self::$settings = [];
		self::$model = null;

		$domain = Router::getDomainByID(DOMAIN_ID);
		$dbSettings = $domain['settings'] ?? [];
		self::$settings = array_merge(Config::_('defaultDomainSettings'), $dbSettings);
	}


	public static function get($record)
	{
		return isset(self::$settings[$record]) ? self::$settings[$record] : NULL;
	}
}
