<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Router;

class IH_Static_PageBase extends Static_Page {

	public $formData;

	public function page_add()
	{
		$this->loadModel();
		$routeId = Router::$POST['routeId'] === 0 ? null : Router::$POST['routeId'];
		$pageTitle = [];
		$pageContent = [];
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			if (isset(Router::$POST['pageTitle-' . $lang[0]])) {
				$pageTitle[$lang[0]] = Router::$POST['pageTitle-' . $lang[0]];
			}
			if (isset(Router::$POST['pageContent-' . $lang[0]])) {
				$pageContent[$lang[0]] = Router::$POST['pageContent-' . $lang[0]];
			}
		}
		$createdBy = Router::$POST['createdBy'];

		$data = [
			'routeId'=>$routeId,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];


		if ($this->model->addPage($data)) {
			return [Router::IH_RESULT['ok'], 'Page has been added.'];
		} else {
			return [Router::IH_RESULT['error'], 'Page couldn\'t be added.'];
		}
	}



	public function page_update()
	{
		$this->loadModel();
		$id = Router::$POST['id'];
		$routeId = Router::$POST['routeId'];

		$pageTitle = [];
		$pageContent = [];

		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			if (isset(Router::$POST['pageTitle-' . $lang[0]])) {
				$pageTitle[$lang[0]] = Router::$POST['pageTitle-' . $lang[0]];
			}
			if (isset(Router::$POST['pageContent-' . $lang[0]])) {
				$pageContent[$lang[0]] = Router::$POST['pageContent-' . $lang[0]];
			}
		}

		$createdBy = Router::$POST['createdBy'];

		$pageData = [
			'id'=>$id,
			'routeId'=>$routeId,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];

		if ($this->model->updatePage($pageData)) {
			return [Router::IH_RESULT['ok'], 'Page has been added.'];
		} else {
			return [Router::IH_RESULT['error'], 'Page couldn\'t be added.'];
		}
	}



	public function page_delete()
	{
		$this->loadModel();

		if ($this->model->deletePage(Router::$POST['id']) ) {
			return [Router::IH_RESULT['ok'], 'Route has been deleted.'];
		} else {
			return [Router::IH_RESULT['error'], 'Route couldn\'t be deleted.'];
		}
	}

}
