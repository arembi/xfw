<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Menu extends EloquentModel {

	public function menuitems(): HasMany
	{
		return $this->hasMany(Menuitem::class);
	}


	public function domains(): BelongsToMany
	{
		return $this->belongsToMany(Domain::class, 'menu_domain', 'menu_id', 'domain_id');
	}
}
