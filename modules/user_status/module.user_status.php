<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\User;

class User_StatusBase extends \Arembi\Xfw\Core\ModuleCore {
  protected static $hasModel = false;

  protected function main(&$options)
  {
    $this->lv('user', $_SESSION['user']);
    $this->lv('username', $_SESSION['user']->get('username'));
    $this->lv('firstName', $_SESSION['user']->get('firstName'));
    $this->lv('lastName', $_SESSION['user']->get('lastName'));
    $this->lv('lastName', $_SESSION['user']->get('lastName'));
    $this->lv('userGroup', $_SESSION['user']->get('userGroup'));
    $this->lv('clearanceLevel', $_SESSION['user']->get('clearanceLevel'));
  }
}
