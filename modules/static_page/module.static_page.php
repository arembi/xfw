<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Inc\Seo;

class Static_PageBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = true;

	public $unavailableInfo;

	protected function main()
	{
		$this->loadModel();
		$this->loadPathParams();

		$this->unavailableInfo = [
			'hu' => [
				'contentTitle' => 'Hamarosan&hellip;',
				'content' => 'Az oldal tartalma feltöltés alatt áll, kérjük látogasson vissza később.'
			],
			'en' => [
				'contentTitle' => 'Coming soon&hellip;',
				'content' => 'The contents of this page have not been uploaded yet.'
			]
		];

		if (!$this->params['id']) {
			$routeId = Router::getMatchedRouteId();
			$page = $this->model->getPageByRouteId($routeId);
		} else {
			$page = $this->model->getPages([$this->params['id']]);
		}

		$lang = App::getLang();

		if (!$page) {
			// If the page was not found
			$contentTitle = $this->unavailableInfo[$lang]['contentTitle'] ?? '';
			$content = $this->unavailableInfo[$lang]['content'] ?? '';
			$id = 0;

			$createdAt = false;
			$createdBy = false;
			$updatedAt = false;

		} else {
			// The page was found, preparing content and meta information
			$id = $page->id;

			if (!empty($page->seoDescription[$lang])) {
				Seo::metaDescription($page->seoDescription[$lang]);
			}
			if (!empty($page->seoTitle[$lang])) {
				Seo::title($page->seoTitle[$lang]);
			}

			// Showing error message when the content is not available in the requested language
			if (!empty($page->pageContent[$lang])) {
				$content = $page->pageContent[$lang];
			} else {
				$content = $this->unavailableInfo[$lang]['content'] ?? '';
			}

			if (!empty($page->pageTitle[$lang])) {
				$contentTitle = $page->pageTitle[$lang];
			} else {
				$contentTitle = $this->unavailableInfo[$lang]['contentTitle'] ?? '';
			}

			$createdAt = $page->createdAt;
			$createdBy = $page->createdBy;
			$updatedAt = $page->updatedAt;

		}

		$this->lv('content', $content);
		$this->lv('contentTitle', $contentTitle);
		$this->lv('createdAt', $createdAt);
		$this->lv('createdBy', $createdBy);
		$this->lv('updatedAt', $updatedAt);
	}

}
