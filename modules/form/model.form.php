<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Models\Form;

class FormBaseModel {

	public function getForm(int $formId)
	{
		return Form::find($formId);
	}

}
