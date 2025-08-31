<?php

namespace Arembi\Xfw\Module;

class CP_FormBase extends Form {

	public static function menu()
	{
		return [];
	}

	public function form_new()
	{
		$form = new Form(['id' => 7], false);

		$form
			->finalize()
			->processLayout()
			->render();
	}
}
