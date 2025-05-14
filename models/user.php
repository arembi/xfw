<?php

namespace Arembi\Xfw\Core\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model {

	protected $table = 'users';

	public function posts(): HasMany
	{
		return $this->hasMany(Post::class, 'author_id');
	}
}
