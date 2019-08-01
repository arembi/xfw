<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\User;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Config;

class IH_DocumentBase extends Document {

	public function login()
	{
		$user = new User(Router::$POST['username']);

		$passwordHash = $user->get('password');

		if (password_verify(Router::$POST['password'], $passwordHash)) {
			$_SESSION['user'] = $user;
			$result = ['OK', 'User `' . Router::$POST['username'] . '` successfully logged in.'];
		} else {
			$result = ['NOK', 'User `' . Router::$POST['username'] . '` couldn\'t log in.'];
		}

		return $result;
	}

	public function logout()
	{
		$_SESSION['user'] = new User('_guest');

		return ['OK', 'User successfully logged out.'];
	}
}
