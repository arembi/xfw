<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Module extends Model {
    protected $fillable = [
      'name',
      'class',
      'priority',
      'version',
      'description',
      'path_param_order'
      ];


    protected function getPathParamOrderAttribute($value)
    {
      return json_decode($value ?? '', true);
    }


    protected function setPathParamOrderAttribute($value)
    {
      return json_encode($value);
    }


    public function routes()
    {
      return $this->hasMany(Route::class);
    }



    public function categories()
    {
      return $this->belongsToMany(Module_Category::class, 'module_module_category', 'module_id', 'module_category_id');
    }

}
