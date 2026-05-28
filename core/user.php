<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Core\Router;
use stdClass;

class User {

	private static ?UserModel $model = null;

	private static bool $instantiated = false;

	private object $data;
	
	/*
	 * Users can be constructed with their ID, or username
	 * The constructor loads the user data,
	 * @param $value: a username or ID
	 * @param $key: identifying method, can be username, ID, or generic
	 * */

	public function __construct(int|string $id, $key = 'username')
	{
		if (!self::$instantiated) {
			// Load the model
			self::$model = new UserModel();
			self::$instantiated = true;
		}

		switch ($key) {
			case 'id':
				$this->data = self::$model->getUserById($id);
				break;
			case 'username':
				$this->data = self::$model->getUserByUsername($id);
				break;
			default:
				$this->data = new stdClass();
				$this->data->username = $id;
				break;
		}
	}


	public function get(string $field)
	{
		return $this->data->$field ?? null;
	}


	public function set(string $field, mixed $value)
	{
		$this->data->$field = $value;
		return $this;
	}


	public function isLoggedIn()
	{
		return $this->data->clearanceLevel > 0;
	}


	public function isAllowedHere()
	{
		return $this->data->clearanceLevel >= Router::getMatchedRoute()->clearanceLevel;
	}


	public function isAllowedToSendInput()
	{
		return $this->data->clearanceLevel >= Settings::get('inputClearance');
	}


	public function isSuperuser()
	{
		return $this->get('userGroup') == 'superuser';
	}
}
