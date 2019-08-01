<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;

class IH_Static_PageBase extends Static_Page {

	public $formData;

	public function page_new()
	{
		$this->loadModel();
		$routeID = Router::$POST['routeID'];
		$pageTitle = [];
		$pageContent = [];
		foreach (\Arembi\Xfw\Core\Settings::_('availableLanguages') as $lang) {
			if (isset(Router::$POST['pageTitle-' . $lang[0]])) {
				$pageTitle[$lang[0]] = Router::$POST['pageTitle-' . $lang[0]];
			}
			if (isset(Router::$POST['pageContent-' . $lang[0]])) {
				$pageContent[$lang[0]] = Router::$POST['pageContent-' . $lang[0]];
			}
		}
		$createdBy = Router::$POST['createdBy'];

		$data = [
			'routeID'=>$routeID,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];

		if ($this->model->newPage($data)) {
			return ['OK', 'Page has been added.'];
		} else {
			return ['NOK', 'Page couldn\'t be added.'];
		}
	}



	public function page_edit()
	{
		$this->loadModel();
		$ID = Router::$POST['ID'];
		$routeID = Router::$POST['routeID'];

		$pageTitle = [];
		$pageContent = [];

		foreach (\Arembi\Xfw\Core\Settings::_('availableLanguages') as $lang) {
			if (isset(Router::$POST['pageTitle-' . $lang[0]])) {
				$pageTitle[$lang[0]] = Router::$POST['pageTitle-' . $lang[0]];
			}
			if (isset(Router::$POST['pageContent-' . $lang[0]])) {
				$pageContent[$lang[0]] = Router::$POST['pageContent-' . $lang[0]];
			}
		}

		$createdBy = Router::$POST['createdBy'];

		$pageData = [
			'ID'=>$ID,
			'routeID'=>$routeID,
			'title'=>$pageTitle,
			'content'=>$pageContent,
			'createdBy'=>$createdBy
		];

		if ($this->model->updatePage($pageData)) {
			return ['OK', 'Page has been added.'];
		} else {
			return ['NOK', 'Page couldn\'t be added.'];
		}
	}



	public function page_delete()
	{
		$this->loadModel();

		if ($this->model->deletePage(Router::$POST['ID']) ) {
			return 'Route has been deleted.';
		} else {
			return 'Route couldn\'t be deleted.';
		}
	}

}
