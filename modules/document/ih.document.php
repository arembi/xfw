<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\User;
use Arembi\Xfw\Core\Router;

class IH_DocumentBase extends Document {

	public function login()
	{
		$user = new User(Router::$POST['username']);
		$passwordHash = $user->get('password');
		
		if (null !== $passwordHash) {
			if (password_verify(Router::$POST['password'], $passwordHash)) {
				$_SESSION['user'] = $user;
				$result = [0, 'User `' . Router::$POST['username'] . '` successfully logged in.'];
			} else {
				$result = [2, 'User `' . Router::$POST['username'] . '` couldn\'t log in.'];
			}
		} else {
			$result = [2, 'User `' . Router::$POST['username'] . '` couldn\'t log in.'];
		}

		return $result;
	}

	public function logout()
	{
		$id = session_id();
		
		session_destroy();

		Debug::alert('Session destoryed. (ID: ' . $id . ')');

		return [0, 'User successfully logged out. Session ' . $id . ' destroyed.'];
	}
}
