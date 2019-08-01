<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as Capsule;

class Database {
	public function __construct($databaseKey)
	{
		$dbc = Config::_('databases')[$databaseKey];

		$capsule = new Capsule;

		$capsule->addConnection([
			'driver'    => $dbc['driver'],
			'host'      => $dbc['host'],
			'database'  => $dbc['name'],
			'username'  => $dbc['user'],
			'password'  => $dbc['password'],
			'charset'   => $dbc['charset'],
			'collation' => $dbc['collation'],
			'prefix'    => $dbc['prefix']
		]);

		// Make this Capsule instance available globally via static methods... (optional)
		$capsule->setAsGlobal();

		// Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
		$capsule->bootEloquent();
	}
}
