<?php

namespace Arembi\Xfw\Core;


use Arembi\Xfw\Module\FormModel;

class Input_HandlerModel {
	
	
	public function getFormById(int $formId)
	{
		$formModel = new FormModel();

		return $formModel->getFormById($formId);
	}


	public function getFormByName(string $formName)
	{
		$formModel = new FormModel();

		return $formModel->getFormByName($formName);
	}

}
