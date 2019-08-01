<?php

namespace Arembi\Xfw\Core;

abstract class Config {

	private static $configFileName = 'config.php';

	private static $config = [

		// The debug mode switch: if set to TRUE, all debug functions will be available, including the debug panel
		'debugMode' => true,

		'logFile' => ENGINE . DS . 'sys.log',

		// The IP address of localhost
		'localhostIP' => [
			'127.0.0.1',
			'192.168.1.2',
			'::1'
		],

		// Charset settings
		'mbLanguage' => 'uni',
		'mbInternalEncoding' => 'UTF-8',

		// These values will be used for the database connection
		'databases' => [
			'sys' => [
				'driver'   => '',
				'host'     => '',
				'user'     => '',
				'password' => '',
				'name'     => '',
				'charset'  => ''
			]
		],

		// Set the session handlig mode here:
		// supported modes:
		// 	- database
		// 	- default
		'sessionStorage' => 'database',

		// Annotations
		'moduleAddons' => [
			'cp' => 'control_panel',
			'ih' => 'input_handler'
		],

		// The file types that will be accepted for requests
		'fileTypesServed' => [
			'css',
			'js',
			'jpg',
			'png',
			'gif',
			'xml',
			'doc',
			'docx',
			'ods',
			'odt',
			'otf',
			'pdf',
			'ttf'
		],

		'baseModels' => [
			'domain',
			'form',
			'link',
			'module',
			'module_category',
			'redirect',
			'route',
			'session',
			'static_page',
			'user',
			'user_group'
		],

		'defaultDomainSettings' => [
			'availableLanguages' => [
				["hu", "hu-HU"],
				["en","en-GB","en-US"],
				["de", "de-DE"]
			],
			'defaultLanguage' => 'hu',
			'defaultModuleLayout' => 'default',
			'defaultDocumentLayout' => 'default',
			'multiLang' => false,
			'URLTrailingSlash' => 'remove',
			'inputClearance' => 0,
			'dateTimeFormat' => [
				'hu' => 'Y-m-d H:i:s',
				'en' => 'm-d-Y H:i:s'
			],
			'paginationParam' => [
				'hu' => 'oldal',
				'en' => 'page',
				'de' => 'seite'
			]
		]
	];



  // Reads data
  public static function init()
  {
		if (file_exists(BASE . DS . self::$configFileName)) {
    	include BASE . DS . self::$configFileName;
    	self::$config = array_merge(self::$config, $config);
		} else {
			die('Something went wrong. Contact the administrator.');
		}
  }


	public static function _($record, $nullReturn = null)
	{
		return isset(self::$config[$record]) ? self::$config[$record] : $nullReturn;
	}


	/*
	 * Alias function for _()
	 * */
	public static function read($record)
	{
		return self::_($record);
	}
}
