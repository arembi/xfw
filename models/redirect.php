<?php

namespace Arembi\Xfw\Core\Models;

use \Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redirect extends Model {
 
	protected function domain(): BelongsTo
	{
		return $this->belongsTo(Domain::class);
	}
}
