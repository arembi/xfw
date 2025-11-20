<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database {

	public static $capsule;

	public static function init()
	{
		self::$capsule = new Capsule;
		self::connect('sys', 'default');
		self::$capsule->setAsGlobal();
		self::$capsule->bootEloquent();
	}


	public static function connect(string $databaseKey, string $name = 'default')
	{
		if (empty(Config::get('databases')[$databaseKey])) {
			Debug::alert("Could not connect to database {$databaseKey}.", "f");
			return;
		}

		$dbc = Config::get('databases')[$databaseKey];

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

		Debug::alert("Connected to database {$databaseKey}, connection name is {$name}.", "o");
	}
}
