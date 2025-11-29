<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model {
	
	protected $fillable = [
		'name',
		'class',
		'priority',
		'version',
		'description',
		'path_param_order'
	];


	protected function path_param_order(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => json_decode($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value
		);
	}


	public function routes(): HasMany
	{
		return $this->hasMany(Route::class);
	}



	public function categories(): BelongsToMany
	{
		return $this->belongsToMany(Module_Category::class, 'module_module_category', 'module_id', 'module_category_id');
	}

}
