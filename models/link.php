<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Link extends Model {

    protected function getPathParamsAttribute($value)
    {
      return \Arembi\Xfw\Misc\decodeIfJson($value, true);
    }


    protected function setPathParamsAttribute($value)
    {
      if (!is_string($value)) {
        $this->attributes['path_params'] = json_encode($value);
      } else {
        $this->attributes['path_params'] = $value;
      }
    }


    protected function getQueryStringAttribute($value)
    {
      return \Arembi\Xfw\Misc\decodeIfJson($value, true);
    }

    protected function setQueryStringAttribute($value)
    {
      $this->attributes['query_string'] = (!is_string($value))
        ? json_encode($value)
        : $value;
    }


    public function route()
    {
      return $this->belongsTo(Route::class);
    }


}
