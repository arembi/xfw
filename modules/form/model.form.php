<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Models\Form;

class FormBaseModel {

	public function getFormById(int $formId)
	{
		return Form::find($formId);
	}


	public function getFormByName(string $name)
	{
		return Form::where('name', $name)->first();
	}

}
