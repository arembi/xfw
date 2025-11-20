<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Html_Content extends Model {

	protected $table = 'html_content';


	protected function title(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => json_decode($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value
		);
	}


	protected function content(): Attribute
	{
		return Attribute::make(
			get: fn (string $value) => json_decode($value ?? '', true),
			set: fn ($value) => !is_string($value) ? json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : $value
		);
	}
}
