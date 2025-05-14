<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use function Arembi\Xfw\Misc\decodeIfJson;

class Route extends Model {

	protected function path(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value
		);
	}


	protected function moduleConfig(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => json_decode($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value
		);
	}

	/*
	protected function getPathAttribute($value)
	{
		return decodeIfJson($value, true);
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
	}*/


	public function domain(): BelongsTo
	{
		return $this->belongsTo(Domain::class);
	}


	public function module(): BelongsTo
	{
		return $this->belongsTo(Module::class);
	}


	public function links(): HasMany
	{
		return $this->hasMany(Link::class);
	}
}
