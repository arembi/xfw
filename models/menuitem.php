<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Menuitem extends Model {

  protected function getItemAttribute($value)
  {
    return \Arembi\Xfw\Misc\decodeIfJSON($value, true);
  }


  protected function setItemAttribute($value)
  {
    return json_encode($value);
  }

  public function menu()
  {
    return $this->belongsTo(Menu::class);
  }

}
