<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;

class User_StatusBase extends ModuleBase {
  
	protected static $hasModel = false;

	protected function init()
	{
		$user = $_SESSION['user'];
		$this->lv('user', $user);
		$this->lv('username', $user->get('username'));
		$this->lv('firstName', $user->get('firstName'));
		$this->lv('lastName', $user->get('lastName'));
		$this->lv('lastName', $user->get('lastName'));
		$this->lv('userGroup', $user->get('userGroup'));
		$this->lv('clearanceLevel', $user->get('clearanceLevel'));
		$this->lv('sessionId', session_id());
	}
}
