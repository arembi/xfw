<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Redirect extends EloquentModel {
 
	protected function domain(): BelongsTo
	{
		return $this->belongsTo(Domain::class);
	}
}
