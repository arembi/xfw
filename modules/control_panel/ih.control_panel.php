<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Misc;

class IH_Control_PanelBase extends Control_Panel {


	public function domain_new()
	{
		$this->loadModel();

		$data = [
			'domain'=>Router::$POST['domain'],
			'protocol'=>Router::$POST['protocol'],
			'settings'=>Router::$POST['settings']
		];

		$result = $this->model->newDomain($data);

		if ($result) {
			return [0, 'Domain has been added.'];
		} else {
			return [2, 'Domain couldn\'t be added due to input error.'];
		}
	}


	public function domain_edit()
	{
		$this->loadModel();

		$domainSettings = Router::$POST['domainSettings'];

		if (is_array($domainSettings)) {
			foreach ($domainSettings as &$s) {
				$s = Misc\decodeIfJson($s);
			}
			unset($s);

			$domainSettings = json_encode($domainSettings);
		} elseif (!json_validate($domainSettings)) {
			$domainSettings = '{}';
		}

		$data = [
			'id'=>Router::$POST['domainId'],
			'domain'=>Router::$POST['domain'],
			'protocol'=>Router::$POST['protocol'],
			'settings'=>$domainSettings
		];

		$result = $this->model->updateDomain($data);

		if ($result) {
			return [0, 'Domain has been updated.'];
		} else {
			return [2, 'Domain couldn\'t be updated due to input error.'];
		}
	}


	public function domain_delete()
	{
		$this->loadModel();

		$result = $this->model->deleteDomain(Router::$POST['domainId']);

		if ($result) {
			return [0, 'Domain has been deleted.'];
		} else {
			return [2, 'Domain couldn\'t be deleted.'];
		}
	}


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
			'domainId'=>DOMAIN_ID,
			'path'=>$path,
			'moduleId'=>Router::$POST['moduleId'],
			'moduleConfig'=>Router::$POST['moduleConfig'],
			'clearanceLevel'=>Router::$POST['clearanceLevel']
		];

		$result = $this->model->newRoute($data);

		if ($result) {
			return [0, 'Route has been added.'];
		} else {
			return [2, 'Route couldn\'t be added due to input error.'];
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

		$moduleConfig = Router::$POST['moduleConfig'];

		if (is_array($moduleConfig)) {
			foreach ($moduleConfig as &$c) {
				$c = Misc\decodeIfJson($c);
			}
			unset($c);

			$moduleConfig = json_encode($moduleConfig);
		} elseif (!json_validate($moduleConfig)) {
			$moduleConfig = '{}';
		}

		$data = [
			'id' => Router::$POST['routeId'],
			'path' => $path,
			'moduleId' => Router::$POST['moduleId'],
			'moduleConfig' => $moduleConfig,
			'clearanceLevel' => Router::$POST['clearanceLevel']
		];

		$result = $this->model->updateRoute($data);

		if ($result) {
			return [0, 'Route has been updated.'];
		} else {
			return [2, 'Route couldn\'t be updated due to input error.'];
		}
	}


	public function route_delete()
	{
		$this->loadModel();

		$result = $this->model->deleteRoute(Router::$POST['routeId']);

		if ($result) {
			return [0, 'Route has been deleted.'];
		} else {
			return [2, 'Route couldn\'t be deleted.'];
		}
	}

}
