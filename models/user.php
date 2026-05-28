<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends EloquentModel {

	protected $table = 'users';

	public function posts(): HasMany
	{
		return $this->hasMany(Post::class, 'author_id');
	}
}
