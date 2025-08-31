<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Session;
use Arembi\Xfw\Core\User;

class IH_DocumentBase extends Document {

	public function login(&$result)
	{
		$user = new User(Router::post('username'));
		$passwordHash = $user->get('password');
		
		if (null !== $passwordHash) {
			if (password_verify(Router::post('password'), $passwordHash)) {
				$_SESSION['user'] = $user;
				$result
					->status(Input_Handler::RESULT_SUCCESS)
					->message('User `' . Router::post('username') . '` successfully logged in.');
			} else {
				$result
					->status(Input_Handler::RESULT_WARNING)
					->message('Wrong passwords for `' . Router::post('username') . '`');
			}
		} else {
			$result
				->status(Input_Handler::RESULT_WARNING)
				->message('User `' . Router::post('username') . '` couldn\'t log in.');
		}
	}

	
	public function logout(&$result)
	{
		$username = $_SESSION['user']->get('name');
		$id = session_id();
		
		if ($username == '_guest') {
			$result
				->status(Input_Handler::RESULT_WARNING)
				->message('User _guest cannot log out.');
		} else {
			Session::reset();
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('User ' . $username . ' successfully logged out.');
		}
	}
}
