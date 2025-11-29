<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use function Arembi\Xfw\Misc\decodeIfJson;

class Link extends Model {

	protected function path_parameters(): Attribute
    {
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }


	protected function query_params(): Attribute
    {
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }


    public function route(): BelongsTo
    {
    	return $this->belongsTo(Route::class);
    }


	public function domain(): HasManyThrough
	{
		return $this->hasManyThrough(Route::class, Domain::class);
	}


}
