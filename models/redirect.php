<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Redirect extends Model {
  public function domain()
  {
    return $this->belongsTo(Domain::class);
  }
}
