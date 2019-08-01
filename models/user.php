<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class User extends Model {

  protected $table = 'users';

  public function posts()
  {
    return $this->hasMany(Post::class, 'author_id');
  }
}
