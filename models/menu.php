<?php

namespace Arembi\Xfw\Core\Models;
use \Illuminate\Database\Eloquent\Model;

class Menu extends Model {

  public function menuitems()
  {
    return $this->hasMany(Menuitem::class);
  }


  public function domains()
  {
    return $this->belongsToMany(Domain::class, 'menu_domain', 'menu_id', 'domain_id');
  }
}
