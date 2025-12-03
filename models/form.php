<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function Arembi\Xfw\Misc\decodeIfJson;

class Form extends Model {

	
	protected function fields(): Attribute
    {
		return Attribute::make(
			get: fn (?string $value) => decodeIfJson($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }


	protected function options(): Attribute
    {
		return Attribute::make(
			get: fn (?string $value) => decodeIfJson($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value) : $value
		);
    }


	public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}
