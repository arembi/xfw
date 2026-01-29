<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class Static_PageBase extends ModuleBase {

	protected static $autoloadModel = true;

	protected static $unavailableInfo = [
		'title'=>[
			'hu'=>'Hamarosan&hellip;',
			'en'=>'Coming soon&hellip;'
		],
		'excerpt'=>[
			'hu'=>'',
			'en'=>''
		],
		'content'=>[
			'hu'=>'Az oldal tartalma feltöltés alatt áll, kérjük látogasson vissza később.',
			'en'=>'The contents of this page have not been uploaded yet.'
		]
	];

	protected $title;
	protected $excerpt;
	protected $content;
	protected $routeId;
	protected $thumbnail;
	protected $createdAt;
	protected $createdBy;
	protected $updatedAt;


	protected function init()
	{
		$this->invokeModel();
		
		$this->title = [];
		$this->excerpt = [];
		$this->content = [];
		$this->routeId = 0;
		$this->createdAt = '';
		$this->createdBy = '';
		$this->updatedAt = '';

		$lang = App::getLang();

		if (!$this->params['id']) {
			$routeId = Router::getMatchedRouteId();
			$page = $this->model->getPageByRouteId($routeId);
		} else {
			$page = $this->model->getPages([$this->params['id']])->first();
		}

		if (!$page) {
			$this
				->title(self::$unavailableInfo['title'] ?? '')
				->excerpt(self::$unavailableInfo['excerpt'] ?? '')
				->content(self::$unavailableInfo['content'] ?? '')
				->thumbnail('');
		} else {
			$this
				->title($page->pageTitle ?? self::$unavailableInfo[$lang]['title'] ?? '')
				->excerpt($page->pageExcerpt ?? self::$unavailableInfo['excerpt'] ?? '')	
				->content($page->pageContent ?? self::$unavailableInfo['content'] ?? '')
				->routeId($page->routeId ?? 0)
				->thumbnail($page->pageThumbnail ?? '')
				->createdBy($page->createdBy)
				->createdAt($page->createdAt)
				->updatedAt($page->updatedAt);
		}
	}


	public function finalize()
	{
		$this
			->lv('title', $this->title)
			->lv('excerpt', $this->excerpt)
			->lv('content', $this->content)
			->lv('createdAt', $this->createdAt)
			->lv('createdBy', $this->createdBy)
			->lv('updatedAt', $this->updatedAt);
	}


	public function title(string|array|null $title = null): string|array|Static_PageBase
	{
		if ($title === null) {
			return $this->title;
		}
		if (is_array($title)) {
			$title = array_filter($title, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}
		$this->title = $title;
		return $this;
	}


	public function excerpt(string|array|null $excerpt = null): string|array|Static_PageBase
	{
		if ($excerpt === null) {
			return $this->excerpt;
		}
		if (is_array($excerpt)) {
			$excerpt = array_filter($excerpt, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}
		$this->excerpt = $excerpt;
		return $this;
	}


	public function content(string|array|null $content = null): string|array|Static_PageBase
	{
		if ($content === null) {
			return $this->content;
		}
		if (is_array($content)) {
			$content = array_filter($content, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}
		$this->content = $content;
		return $this;
	}


	public function thumbnail(?string $url = null): string|Static_PageBase
	{
		if ($url === null) {
			return $this->thumbnail;
		}
		$this->thumbnail = $url;
		return $this;
	}


	public function routeId(?int $routeId = null): int|null|Static_PageBase
	{
		if ($routeId === null) {
			return $this->routeId;
		}
		$this->routeId = $routeId;
		return $this;
	}


	public function createdAt(?string $createdAt = null): string|Static_PageBase
	{
		if ($createdAt === null) {
			return $this->createdAt;
		}
		$this->createdAt = $createdAt;
		return $this;
	}


	public function createdBy(?string $createdBy = null): string|Static_PageBase
	{
		if ($createdBy === null) {
			return $this->createdBy;
		}
		$this->createdBy = $createdBy;
		return $this;
	}


	public function updatedAt(?string $updatedAt = null): string|Static_PageBase
	{
		if ($updatedAt === null) {
			return $this->updatedAt;
		}
		$this->updatedAt = $updatedAt;
		return $this;
	}

}
