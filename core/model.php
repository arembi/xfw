<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database {

	public static $capsule;

	public static function init()
	{
		self::$capsule = new Capsule;

		self::connect('sys');

		// Make this Capsule instance available globally via static methods... (optional)
		self::$capsule->setAsGlobal();

		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		self::$capsule->bootEloquent();
	}


	public static function connect(string $databaseKey, string $name = 'default')
	{
		$dbc = Config::_('databases')[$databaseKey];

		self::$capsule->addConnection([
			'driver'    => $dbc['driver'],
			'host'      => $dbc['host'],
			'database'  => $dbc['name'],
			'username'  => $dbc['user'],
			'password'  => $dbc['password'],
			'charset'   => $dbc['charset'],
			'collation' => $dbc['collation'],
			'prefix'    => $dbc['prefix']
		], $name);
	}
}
