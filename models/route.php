<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Route extends Model {

  protected function getPathAttribute($value)
  {
    return \Arembi\Xfw\Misc\decodeIfJSON($value, true);
  }


  protected function setPathAttribute($value)
  {
    if (!is_string($value)) {
      $this->attributes['path'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
      $this->attributes['path'] = $value;
    }
  }


  protected function getModuleConfigAttribute($value)
  {
    return json_decode($value ?? '', true);
  }


  protected function setModuleConfigAttribute($value)
  {
    if (!is_string($value)) {
      $this->attributes['module_config'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } else {
      $this->attributes['module_config'] = $value;
    }
  }


  public function domain()
  {
    return $this->belongsTo(Domain::class);
  }


  public function module()
  {
    return $this->belongsTo(Module::class);
  }


  public function links()
  {
    return $this->hasMany(Link::class);
  }
}
