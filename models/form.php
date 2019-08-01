<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Form extends Model {
  protected function getFieldsAttribute($value)
  {
    return json_decode($value, true);
  }


  protected function setFieldsAttribute($value)
  {
    if (!is_string($value)) {
      $this->attributes['fields'] = json_encode($value);
    } else {
      $this->attributes['fields'] = $value;
    }
  }
}
