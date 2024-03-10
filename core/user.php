<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Core\Router;

class User {

	private static $model = null;

	private static $instantiated = false;

	private $data = [];
	
	/*
	 * Users can be constructed with their ID, or username
	 * The constructor loads the user data,
	 * @param $value: a username or ID
	 * @param $key: identifying method, can be username or ID
	 * */

	public function __construct($value, $key = 'username')
	{
		if (!self::$instantiated) {
			// Load the model
			self::$model = new UserModel();
			self::$instantiated = true;
		}

		if ($key == 'ID') {
			$this->data = self::$model->getUserByID($value);
		} elseif ($key == 'username') {
			if ($value != '_guest') {
				$this->data = self::$model->getUserByUsername($value);
			} else {
				// Loading default values
				$this->data = new \stdClass();
				$this->data->domain = DOMAIN;
				$this->data->ID = 0;
				$this->data->username = '_guest';
				$this->data->firstName = 'Guest';
				$this->data->lastName = 'User';
				$this->data->userGroup = 'N/A';
				$this->data->clearanceLevel = 0;
			}
		}
	}


	public function get($field)
	{
		return $this->data->$field ?? null;
	}


	public function set($field, $value)
	{
		$this->data->$field = $value;
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
