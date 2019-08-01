<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Module\HEAD;

class Static_PageBase extends \Arembi\Xfw\Core\ModuleCore {

	public $unavailableInfo;

	protected function main(&$options)
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

		if (empty($options['ID'])) {
			$routeID = Router::getMatchedRouteID();
			$page = $this->model->getPageByRouteID($routeID);
		} else {
			$ID = $options['ID'];
			$page = $this->model->getPages([$ID]);
		}

		$lang = App::getLang();

		if (!$page) {
			// If the page was not found
			$contentTitle = $this->unavailableInfo[$lang]['contentTitle'] ?? '';
			$content = $this->unavailableInfo[$lang]['content'] ?? '';
			$ID = 0;
			HEAD::setTitle($this->unavailableInfo[$lang]['contentTitle'] ?? '', __CLASS__);

			$createdAt = false;
			$createdBy = false;
			$updatedAt = false;

		} else {
			// The page was found, preparing content and meta information
			$ID = $page->ID;

			if (!empty($page->seoDescription[$lang])) {
				HEAD::setDescription($page->seoDescription[$lang], __CLASS__);
			}
			if (!empty($page->seoKeywords[$lang])) {
				HEAD::setKeywords($page->seoKeywords[$lang], __CLASS__);
			}
			if (!empty($page->seoTitle[$lang])) {
				HEAD::setTitle($page->seoTitle[$lang], __CLASS__);
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
