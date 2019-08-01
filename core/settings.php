<?php

//~ The settings are the configuration parameters read from the database
//~ They dont mix with the config class items

namespace Arembi\Xfw\Core;

class Settings {
	private static $settings = [];

	private static $model = null;


	public static function init()
	{
		$domain = Router::getDomainByID(DOMAIN_ID);
		$dbSettings = $domain['settings'] ?? [];
		self::$settings = array_merge(Config::_('defaultDomainSettings'), $dbSettings);
	}


	public static function _($record)
	{
		return isset(self::$settings[$record]) ? self::$settings[$record] : NULL;
	}


	public static function read($record)
	{
		return self::_($record);
	}
}
