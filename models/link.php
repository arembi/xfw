<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

use function Arembi\Xfw\Misc\decodeIfJson;

class Link extends Model {

	protected function pathParams(): Attribute
    {
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }


	protected function queryString(): Attribute
    {
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value, true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }

    /*protected function getPathParamsAttribute($value)
    {
    	return decodeIfJson($value, true);
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
    	return decodeIfJson($value, true);
    }

    protected function setQueryStringAttribute($value)
    {
    	$this->attributes['query_string'] = (!is_string($value))
        	? json_encode($value)
        	: $value;
    }*/


    public function route(): BelongsTo
    {
    	return $this->belongsTo(Route::class);
    }


	public function domain(): HasManyThrough
	{
		return $this->hasManyThrough(Route::class, Domain::class);
	}


}
