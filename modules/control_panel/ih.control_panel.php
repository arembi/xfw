<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Misc;

class IH_Control_PanelBase extends Control_Panel {


	public function domain_new(&$result)
	{
		$this->loadModel();

		$data = [
			'domain'=>Router::post('domain'),
			'protocol'=>Router::post('protocol'),
			'settings'=>Router::post('settings')
		];

		if ($this->model->newDomain($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Domain has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Domain couldn\'t be added due to input error.');
		}
	}


	public function domain_edit(&$result)
	{
		$this->loadModel();

		$domainSettings = Router::post('domainSettings');

		if (is_array($domainSettings)) {
			foreach ($domainSettings as &$s) {
				$s = Misc\decodeIfJson($s);
			}
			unset($s);

			$domainSettings = json_encode($domainSettings,JSON_UNESCAPED_UNICODE);
		} elseif (!json_validate($domainSettings)) {
			$domainSettings = '{}';
		}

		$data = [
			'id'=>Router::post('domainId'),
			'domain'=>Router::post('domain'),
			'protocol'=>Router::post('protocol'),
			'settings'=>$domainSettings
		];

		if ($this->model->updateDomain($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Domain has been updated.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Domain couldn\'t be updated due to input error.');
		}
	}


	public function domain_delete(&$result)
	{
		$this->loadModel();

		if ($this->model->deleteDomain(Router::post('domainId'))) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Domain has been deleted.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Domain couldn\'t be deleted.');
		}
	}


	public function route_new(&$result)
	{
		$this->loadModel();

		$path = [];
		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('path-' . $lang[0]) !== null) {
				$path[$lang[0]] = Router::post('path-' . $lang[0]);
			}
		}

		$data = [
			'domainId'=>DOMAIN_ID,
			'path'=>$path,
			'moduleId'=>Router::post('moduleId'),
			'moduleConfig'=>Router::post('moduleConfig'),
			'clearanceLevel'=>Router::post('clearanceLevel')
		];

		if ($this->model->newRoute($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Route has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Route couldn\'t be added due to input error.');
		}
	}


	public function route_edit(&$result)
	{
		$this->loadModel();

		$path = [];
		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('path-' . $lang[0]) !== null) {
				$path[$lang[0]] = Router::post('path-' . $lang[0]);
			}
		}

		$moduleConfig = Router::post('moduleConfig');

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
			'id' => Router::post('routeId'),
			'path' => $path,
			'moduleId' => Router::post('moduleId'),
			'moduleConfig' => $moduleConfig,
			'clearanceLevel' => Router::post('clearanceLevel')
		];

		if ($this->model->updateRoute($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Route has been updated.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Route couldn\'t be updated due to input error.');
		}
	}


	public function route_delete(&$result)
	{
		$this->loadModel();

		if ($this->model->deleteRoute(Router::post('routeId'))) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Route has been deleted.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Route couldn\'t be deleted.');
		}
	}

}