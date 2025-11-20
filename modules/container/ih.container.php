<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class IH_ContainerBase extends Container {

	public $formData;

	public function content_new(&$result)
	{
		$this->invokeModel();
		
		$title = [];
		$content = [];
		$data = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('title-' . $lang[0]) !== null) {
				$title[$lang[0]] = Router::post('title-' . $lang[0]);
			}
			if (Router::post('content-' . $lang[0]) !== null) {
				$content[$lang[0]] = Router::post('content-' . $lang[0]);
			}
		}
		
		$data = [
			'title'=>$title,
			'content'=>$content
		];


		if ($this->model->addContent($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Content has been added.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Content couldn\'t be added.');
		}
	}



	public function content_update(&$result)
	{
		$this->invokeModel();

		$id = Router::post('id');

		$title = [];
		$content = [];
		$data = [];

		foreach (Settings::get('availableLanguages') as $lang) {
			if (Router::post('title-' . $lang[0]) !== null) {
				$title[$lang[0]] = Router::post('title-' . $lang[0]);
			}
			if (Router::post('content-' . $lang[0]) !== null) {
				$content[$lang[0]] = Router::post('content-' . $lang[0]);
			}
		}

		$data = [
			'id'=>$id,
			'title'=>$title,
			'content'=>$content
		];

		if ($this->model->updateContent($data)) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Content has been updated.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Content could not be updated.');
		}
	}



	public function content_delete(&$result)
	{
		$this->invokeModel();

		if ($this->model->deleteContent(Router::post('id')) ) {
			$result
				->status(Input_Handler::RESULT_SUCCESS)
				->message('Content has been deleted.');
		} else {
			$result
				->status(Input_Handler::RESULT_ERROR)
				->message('Content could not be deleted.');
		}
	}

}
