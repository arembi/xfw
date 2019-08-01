<?php

namespace Arembi\Xfw\Module;
use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Form;

class FormBaseModel {

	public function getForm(int $formID)
	{
		return Form::find($formID);
	}

}
