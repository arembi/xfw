<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\User;

class User_StatusBase extends ModuleBase {
  
	protected static $autoloadModel = false;
	protected $user;

	protected function init()
	{
		$this->user($_SESSION['user']);
	}


	public function finalize()
	{
		$this
			->lv('user', $this->user)
			->lv('username', $this->user->get('username'))
			->lv('firstName', $this->user->get('firstName'))
			->lv('lastName', $this->user->get('lastName'))
			->lv('lastName', $this->user->get('lastName'))
			->lv('userGroup', $this->user->get('userGroup'))
			->lv('clearanceLevel', $this->user->get('clearanceLevel'))
			->lv('sessionId', session_id());
	}


	public function user(?User $user = null): User|User_StatusBase
	{
		if ($user === null) {
			return $this->user;
		}
		$this->user = $user;
		return $this;
	}
}
