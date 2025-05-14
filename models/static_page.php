<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Static_Page extends Model {

	protected $table = 'static_pages';


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

	/*
	protected function getTitleAttribute($value)
	{
		return json_decode($value ?? '', true);
	}


	protected function setTitleAttribute($value)
	{
		$this->attributes['title'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}


	protected function getContentAttribute($value)
	{
		return json_decode($value ?? '', true);
	}


	protected function setContentAttribute($value)
	{
		$this->attributes['content'] = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	}*/


	public function creator(): BelongsTo
	{
		return $this->belongsTo(User::class, 'id', 'created_by');
	}
}
