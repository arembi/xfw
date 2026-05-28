<?php

namespace Arembi\Xfw\Core\Models;

use \Illuminate\Database\Eloquent\Model as EloquentModel;

class Session extends EloquentModel {
	
	protected $keyType = 'string';
	public $incrementing = false;
	public $timestamps = false;

}
