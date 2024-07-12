<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class IH_Control_PanelBase extends Control_Panel {

	public $formData;


	public function route_new()
	{
		$this->loadModel();

		$path = [];
		foreach (Settings::get('availableLanguages') as $lang) {
			if (isset(Router::$POST['path-' . $lang[0]])) {
				$path[$lang[0]] = Router::$POST['path-' . $lang[0]];
			}
		}

		$data = [
			'domainID'=>DOMAIN_ID,
			'path'=>$path,
			'moduleID'=>Router::$POST['moduleID'],
			'moduleConfig'=>Router::$POST['moduleConfig'],
			'clearanceLevel'=>Router::$POST['clearanceLevel']
		];

		$result = $this->model->newRoute($data);

		if ($result) {
			return ['OK', 'Route has been added.'];
		} else {
			return ['NOK', 'Route couldn\'t be added due to input error.'];
		}
	}


	public function route_edit()
	{
		$this->loadModel();

		$path = [];
		foreach (Settings::get('availableLanguages') as $lang) {
			if (isset(Router::$POST['path-' . $lang[0]])) {
				$path[$lang[0]] = Router::$POST['path-' . $lang[0]];
			}
		}

		$data = [
			'ID' => Router::$POST['routeID'],
			'path' => $path,
			'moduleID' => Router::$POST['moduleID'],
			'moduleConfig' => Router::$POST['moduleConfig'],
			'clearanceLevel' => Router::$POST['clearanceLevel']
		];

		$result = $this->model->updateRoute($data);

		if ($result) {
			return ['OK', 'Route has been updated.'];
		} else {
			return ['NOK', 'Route couldn\'t be updated due to input error.'];
		}
	}


	public function route_delete()
	{
		$this->loadModel();

		$delOK = true;

		if (!isset(Router::$POST['routeID']) || !is_numeric(Router::$POST['routeID'])) {
			$delOK = false;
		}

		if ($delOK) {
			$this->model->deleteRoute(Router::$POST['routeID']);
			return ['OK', 'Route has been deleted.'];
		} else {
			return ['NOK', 'Route couldn\'t be deleted.'];
		}
	}

}
