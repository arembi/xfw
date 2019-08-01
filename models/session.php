<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Session extends Model {
    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

}
