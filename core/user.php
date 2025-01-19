<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Core\Router;

class User {

	private static $model = null;

	private static $instantiated = false;

	private $data;
	
	/*
	 * Users can be constructed with their ID, or username
	 * The constructor loads the user data,
	 * @param $value: a username or ID
	 * @param $key: identifying method, can be username, ID, or generic
	 * */

	public function __construct($value, $key = 'username')
	{
		if (!self::$instantiated) {
			// Load the model
			self::$model = new UserModel();
			self::$instantiated = true;
		}

		switch ($key) {
			case 'id':
				$this->data = self::$model->getUserById($value);
				break;
			case 'username':
				$this->data = self::$model->getUserByUsername($value);
				break;
			default:
				$this->data = new \stdClass();
				$this->data->username = $value;
				break;
		}
	}


	public function get($field)
	{
		return $this->data->$field ?? null;
	}


	public function set($field, $value)
	{
		$this->data->$field = $value;
		return $this;
	}


	public function isLoggedIn()
	{
		return $this->data->clearanceLevel > 0;
	}


	public function allowedHere()
	{
		return $this->data->clearanceLevel >= Router::getMatchedRoute()->clearanceLevel;
	}


	public function allowedToSendInput()
	{
		return $this->data->clearanceLevel >= Settings::get('inputClearance');
	}


	public function isSuperuser()
	{
		return $this->get('userGroup') == 'superuser';
	}
}
