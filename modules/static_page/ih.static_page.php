<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class IH_Static_PageBase extends Static_Page {

	public $formData;

	public function page_new(&$result)
	{
		$this->loadModel();
		$routeId = Router::post('routeId') === 0 ? null : Router::post('routeId');
		$pageTitle = [];
		$pageContent = [];
		$createdBy = '';
		$data = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('pageTitle-' . $lang[0]) !== null) {
				$pageTitle[$lang[0]] = Router::post('pageTitle-' . $lang[0]);
			}
			if (Router::post('pageContent-' . $lang[0]) !== null) {
				$pageContent[$lang[0]] = Router::post('pageContent-' . $lang[0]);
			}
		}
		$createdBy = Router::post('createdBy');

		$data = [
			'routeId'=>$routeId,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];


		if ($this->model->addPage($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Page has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Page couldn\'t be added.');
		}
	}



	public function page_update(&$result)
	{
		$this->loadModel();
		$id = Router::post('id');
		$routeId = Router::post('routeId');

		$pageTitle = [];
		$pageContent = [];
		$createdBy = '';
		$pageData = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('pageTitle-' . $lang[0]) !== null) {
				$pageTitle[$lang[0]] = Router::post('pageTitle-' . $lang[0]);
			}
			if (Router::post('pageContent-' . $lang[0]) !== null) {
				$pageContent[$lang[0]] = Router::post('pageContent-' . $lang[0]);
			}
		}

		$createdBy = Router::post('createdBy');

		$pageData = [
			'id'=>$id,
			'routeId'=>$routeId,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];

		if ($this->model->updatePage($pageData)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Page has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Page couldn\'t be added.');
		}
	}



	public function page_delete(&$result)
	{
		$this->loadModel();

		if ($this->model->deletePage(Router::post('id')) ) {
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
