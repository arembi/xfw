<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use function Arembi\Xfw\Misc\decodeIfJson;

class Menuitem extends Model {

	protected function item(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => decodeIfJson($value),
			set: fn ($value) => json_encode($value)
		);
	}
	

	public function menu(): BelongsTo
	{
		return $this->belongsTo(Menu::class);
	}

}
