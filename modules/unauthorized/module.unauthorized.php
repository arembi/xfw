<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;

class UnauthorizedBase extends ModuleBase {
	
	protected static $autoloadModel = false;
	
	
	protected function init()
	{
		$this
			->autoAction(false)
			->autoFinalize(false);
	}
}
