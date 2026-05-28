<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Models\Form;
use Arembi\Xfw\Core\Model;

class FormBaseModel extends Model {

	public function getFormById(int $formId)
	{
		return Form::find($formId);
	}


	public function getFormByName(string $name)
	{
		return Form::where('name', $name)->first();
	}

}
