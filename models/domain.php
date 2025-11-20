<?php

namespace Arembi\Xfw\Core\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use function Arembi\Xfw\Misc\decodeIfJson;


class Domain extends Model {

	protected function settings(): Attribute
	{
		return Attribute::make(
            get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value,JSON_UNESCAPED_UNICODE) : $value
        );
	}
	

    public function routes(): HasMany
    {
		return $this->hasMany(Route::class);
    }


    public function menus(): BelongsToMany
    {
    	return $this->belongsToMany(Menu::class, 'menu_domain', 'domain_id', 'menu_id' );
    }
}
