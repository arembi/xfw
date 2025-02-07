<?php
// Domain-based configuration parameters from the database

namespace Arembi\Xfw\Core;

class Settings {
	private static $settings;

	public static function init()
	{
		self::$settings = [];

		$domain = Router::getDomainRecordById(DOMAIN_ID);
		$dbSettings = $domain['settings'] ?? [];
		self::$settings = array_merge(Config::get('defaultDomainSettings'), $dbSettings);
	}


	public static function get($record)
	{
		return isset(self::$settings[$record]) ? self::$settings[$record] : null;
	}


	public static function _($record)
	{
		return self::get($record);
	}
}
