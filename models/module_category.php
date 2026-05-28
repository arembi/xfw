<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module_Category extends EloquentModel {

    protected $table = 'module_categories';

    public function modules(): BelongsToMany
    {
    	return $this->belongsToMany(Module::class, 'module_module_category', 'module_category_id', 'module_id' );
    }
}
