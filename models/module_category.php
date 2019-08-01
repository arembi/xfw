<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Module_Category extends Model {

    protected $table = 'module_categories';

    public function modules()
    {
      return $this->belongsToMany(Module::class, 'module_module_category', 'module_category_id', 'module_id' );
    }
}
