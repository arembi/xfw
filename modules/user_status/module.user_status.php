<?php

namespace Arembi\Xfw\Module;

class User_StatusBase extends \Arembi\Xfw\Core\ModuleCore {
  
	protected static $hasModel = false;

	protected function main()
	{
		$this->lv('user', $_SESSION['user']);
		$this->lv('username', $_SESSION['user']->get('username'));
		$this->lv('firstName', $_SESSION['user']->get('firstName'));
		$this->lv('lastName', $_SESSION['user']->get('lastName'));
		$this->lv('lastName', $_SESSION['user']->get('lastName'));
		$this->lv('userGroup', $_SESSION['user']->get('userGroup'));
		$this->lv('clearanceLevel', $_SESSION['user']->get('clearanceLevel'));
		$this->lv('sessionId', session_id());
	}
}
