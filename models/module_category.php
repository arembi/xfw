<?php

namespace Arembi\Xfw\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module_Category extends Model {

    protected $table = 'module_categories';

    public function modules(): BelongsToMany
    {
    	return $this->belongsToMany(Module::class, 'module_module_category', 'module_category_id', 'module_id' );
    }
}
