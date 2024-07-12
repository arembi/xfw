<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\User;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Session;

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
		$username = $_SESSION['user']->get('name');
		$id = session_id();
		
		if ($username == '_guest') {
			return [1, 'User _guest cannot log out.'];
		}
		
		Session::reset();

		return [0, 'User ' . $username . ' successfully logged out.'];
	}
}
