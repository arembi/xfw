<?php

namespace Arembi\Xfw\Module;

class CP_FormBase extends Form {
	public function form_new()
	{
		$form = new Form(['ID' => 7], FALSE);

		$form->build();

		return $form->processLayout()->getLayoutHTML();
	}
}
