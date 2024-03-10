<?php

namespace Arembi\Xfw\Core;
use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Form;

class Input_HandlerModel {
	public function getFormByID(int $formID)
	{
		$form = DB::table('forms')
			->leftJoin('form_module', 'forms.id', '=', 'form_module.form_id')
			->leftJoin('modules', 'form_module.module_id', '=', 'modules.id')
			->where('forms.id', $formID)
			->select(
				'forms.id AS formID',
				'forms.name AS formName',
				'forms.fields AS formFields',
				'forms.action_url AS actionUrl',
				'modules.name AS moduleName'
				)
			->first();

		if ($form) {
			$form->formFields = json_decode($form->formFields ?? '', true);
		}

		return $form;
	}



	public function getFormByName(string $formName)
	{
		$form = DB::table('forms')
			->leftJoin('form_module', 'forms.id', '=', 'form_module.form_id')
			->leftJoin('modules', 'form_module.module_id', '=', 'modules.id')
			->where('forms.name', $formName)
			->select(
				'forms.id AS formID',
				'forms.name AS formName',
				'forms.fields AS formFields',
				'forms.action_url AS actionUrl',
				'modules.name AS moduleName'
				)
			->first();

		if ($form) {
			$form->formFields = json_decode($form->formFields ?? '', true);
		}

		return $form;
	}

}
