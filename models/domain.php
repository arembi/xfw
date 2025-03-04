<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Domain extends Model {

    protected function getSettingsAttribute($value)
    {
      return \Arembi\Xfw\Misc\decodeIfJson($value, true);
    }


    protected function setSettingsAttribute($value)
    {
      if (!is_string($value)) {
        $this->attributes['settings'] = json_encode($value,JSON_UNESCAPED_UNICODE);
      } else {
        $this->attributes['settings'] = $value;
      }

    }


    public function routes()
    {
      return $this->hasMany(Route::class);
    }


    public function menus()
    {
      return $this->belongsToMany(Menu::class, 'menu_domain', 'domain_id', 'menu_id' );
    }
}
