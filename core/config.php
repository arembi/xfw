<?php

namespace Arembi\Xfw\Core;

use RuntimeException;

abstract class Config {

	private static $configFileName = 'config.php';

	private static $config = [

		'debugMode' => true,

		'logFile' => ENGINE_DIR . DS . 'sys.log',

		'localhostIP' => [
			'127.0.0.1',
			'192.168.1.2',
			'::1'
		],

		'mbLanguage' => 'uni',
		'mbInternalEncoding' => 'UTF-8',

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

		//Supported modes:
		//	database
		//	default
		'sessionStorage' => 'database',

		'moduleAddons' => [
			'cp' => 'control_panel',
			'ih' => 'input_handler'
		],

		'fileTypesServed' => [
			'css',
			'js',
			'jpg',
			'png',
			'gif',
			'xml',
			'csv',
			'xls',
			'xlsx',
			'doc',
			'docx',
			'ods',
			'odt',
			'otf',
			'pdf',
			'ttf'
		],

		'uploadMaxFileSize' => 10000000,

		'uploadAcceptedMimeTypes' => [
			'jpg'=>'image/jpeg',
			'png'=>'image/png',
			'gif'=>'image/gif',
			'csv'=>'text/csv',
			'csv_2'=>'text/plain',
			'xml'=>'text/xml',
			'xml_2'=>'application/xml',
			'xml_3'=>'text/plain'
		],

		'baseModels' => [
			'domain',
			'form',
			'link',
			'menu',
			'menuitem',
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
				['hu', 'hu-HU'],
				['en','en-GB','en-US'],
				['de', 'de-DE']
			],
			'defaultLanguage' => 'hu',
			'defaultModuleLayout' => 'default',
			'defaultDocumentLayout' => 'default',
			'defaultDocumentLayoutVariant' => 'default',
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
			],
			'siteName' => [
				'hu' => 'XFW Oldal',
				'en' => 'XFW Site',
				'de' => 'XFW Seite'
			]
		]
	];


	public static function init()
	{
		if (file_exists(INDEX_DIR . DS . self::$configFileName)) {
			include INDEX_DIR . DS . self::$configFileName;
			self::$config = array_merge(self::$config, $config);
		} else {
			exit('Something went wrong. Contact the administrator.');
		}
	}


	public static function get($record, $NA = null)
	{
		return isset(self::$config[$record]) ? self::$config[$record] : $NA;
	}

}
