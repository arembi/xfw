<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class IH_Static_PageBase extends Static_Page {

	public $formData;

	public function page_new(&$result)
	{
		$this->invokeModel();
		
		$routeId = Router::post('routeId') === 0 ? null : Router::post('routeId');
		$pageTitle = [];
		$pageExcerpt = [];
		$pageContent = [];
		$pageThumbnail = Router::post('thumbnail');
		$createdBy = Router::post('createdBy');
		$data = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('pageTitle-' . $lang[0]) !== null) {
				$pageTitle[$lang[0]] = Router::post('pageTitle-' . $lang[0]);
			}
			if (Router::post('pageExcerpt-' . $lang[0]) !== null) {
				$pageExcerpt[$lang[0]] = Router::post('pageExcerpt-' . $lang[0]);
			}
			if (Router::post('pageContent-' . $lang[0]) !== null) {
				$pageContent[$lang[0]] = Router::post('pageContent-' . $lang[0]);
			}
		}
		$data = [
			'routeId'=>$routeId,
			'title'=>$pageTitle,
			'excerpt'=>$pageExcerpt,
			'content'=>$pageContent,
			'thumbnail'=>$pageThumbnail,
			'createdBy'=>$createdBy
		];


		if ($this->model->addPage($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Page has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Page could not be added.');
		}
	}



	public function page_update(&$result)
	{
		$this->invokeModel();
		$id = Router::post('id');
		$routeId = Router::post('routeId');

		$pageTitle = [];
		$pageExcerpt = [];
		$pageContent = [];
		$pageThumbnail = Router::post('thumbnail');
		$createdBy = Router::post('createdBy');
		$pageData = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('pageTitle-' . $lang[0]) !== null) {
				$pageTitle[$lang[0]] = Router::post('pageTitle-' . $lang[0]);
			}
			if (Router::post('pageExcerpt-' . $lang[0]) !== null) {
				$pageExcerpt[$lang[0]] = Router::post('pageExcerpt-' . $lang[0]);
			}
			if (Router::post('pageContent-' . $lang[0]) !== null) {
				$pageContent[$lang[0]] = Router::post('pageContent-' . $lang[0]);
			}
		}

		$pageData = [
			'id'=>$id,
			'routeId'=>$routeId,
			'excerpt'=>$pageExcerpt,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'thumbnail'=>$pageThumbnail,
			'createdBy'=>$createdBy
		];

		if ($this->model->updatePage($pageData)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Page has been updated.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Page could not be updated.');
		}
	}


	public function page_delete(&$result)
	{
		$this->invokeModel();

		if ($this->model->deletePage(Router::post('id')) ) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Page has been deleted.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Page could not be deleted.');
		}
	}

}
