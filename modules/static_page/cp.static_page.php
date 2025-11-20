<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class CP_Static_PageBase extends Static_Page {

	public static function menu()
	{
		return [
			'title' => [
				'hu' => 'Statikus Oldalak',
				'en' => 'Static Pages'
			],
			'items' => [
				['home', [
					'hu' => 'Info',
					'en' => 'Info']
				],
				['page_list', [
					'hu' => 'Lista',
					'en' => 'List']
				],
				['page_new', [
					'hu' => 'Új',
					'en' => 'New']
				]
			]
		];
	}

	public function homeAction()
	{
		$welcomeMessage = 'The static page module can be used to display content with little or no dynamic parts, and which do not require to be organized into hierarchy.';
		$this->lv('text', $welcomeMessage);
	}



	public function page_listAction()
	{
		$pages = $this->model->getPagesByDomainId(DOMAIN_ID);

		$avaliableLanguages = Settings::get('availableLanguages');
		
		foreach ($pages as &$page) {
			$page->route = Router::getRouteRecordById($page->routeId);
			$editLink = new Link(['anchor' => 'edit', 'href' => '?task=page_edit&id=' . $page->id, 'autoFinalize'=>true]);
			$page->editLink = $editLink->processLayout()->getLayoutHtml();
			$deleteLink = new Link(['anchor' => 'delete', 'href' => '?task=page_delete&id=' . $page->id, 'autoFinalize'=>true]);
			$page->deleteLink = $deleteLink->processLayout()->getLayoutHtml();
		}
		unset($page);

		$this->lv('availableLanguages', $avaliableLanguages);
		$this->lv('pages', $pages);
	}



	public function page_newAction()
	{
		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_new']);
		$avaliableLanguages = Settings::get('availableLanguages');

		foreach ($avaliableLanguages as $lang) {
			$form->addField('pageTitle-' . $lang[0])
				->label('Title (' . $lang[0] . ')');
		}

		foreach ($avaliableLanguages as $lang) {
			$form->addField('pageExcerpt-' . $lang[0], 'textarea')
				->label('Excerpt (' . $lang[0] . ')');
		}

		foreach ($avaliableLanguages as $lang) {
			$form->addField('pageContent-' . $lang[0], 'textarea')
				->label('Content (' . $lang[0] . ')');
		}

		$form->addField('thumbnail')
			->label('Thumbnail');

		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$createdBySelectOptions[$user->username] = ['value' => $user->id];
		}
		$form->addField('createdBy', 'select')
			->label('Creator')
			->options($createdBySelectOptions);


		$routeIdSelectOptions = [
			"[unpublished]"=>['value'=>0]
		];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$routeIdSelectOptions[$option] = ['value' => $id];
		}

		$form->addField('routeId', 'select')
			->label('Path')
			->options($routeIdSelectOptions);

		$form
			->lv('availableLanguages', $avaliableLanguages)
			->finalize();

		$this->lv('form', $form);
	}



	public function page_editAction()
	{
		$page = $this->model->getPageById(Router::get('id'));
		$avaliableLanguages = Settings::get('availableLanguages');

		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_update']);
		$form
			->layout('page_edit')
			->layoutVariant('page_edit');

		// ID
		$form->addField('id')
			->attributes(['value' => $page->id, 'readonly' => true])
			->label('ID');

		// pageTitle
		foreach ($avaliableLanguages as $lang) {
			$pageTitle = $page->pageTitle[$lang[0]] ?? '';
			
			$form->addField('pageTitle-' . $lang[0])
				->label('Title (' . $lang[0] . ')')
				->attribute('value', $pageTitle);
		}

		// pageExcerpt
		foreach ($avaliableLanguages as $lang) {
			$pageExcerpt = $page->pageExcerpt[$lang[0]] ?? '';
			
			$form->addField('pageExcerpt-' . $lang[0], 'textarea')
				->label('Excerpt (' . $lang[0] . ')')
				->text($pageExcerpt);
		}

		// pageContent
		foreach ($avaliableLanguages as $lang) {
			$pageContent = $page->pageContent[$lang[0]] ?? '';
			
			$form->addField('pageContent-' . $lang[0], 'textarea')
				->attribute('class', 'rt-editor')
				->label('Content (' . $lang[0] . ')')
				->text($pageContent);
		}

		// pageThumbnail
		$pageThumbnail = $page->thumbnail ?? '';
		$form->addField('thumbnail')
			->label('Thumbnail')
			->attribute('value', $pageThumbnail);

		// createdBy
		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$attributes = ['value' => $user->id];
			if ($user->id === $page->createdBy) {
				$attributes['selected'] = 'selected';
			}
			$createdBySelectOptions[$user->username] = $attributes;
		}
		$form->addField('createdBy', 'select')
			->label('Szerző')
			->options($createdBySelectOptions);

		// routeID
		$routeIdSelectOptions = ['none' => ['value' => 0]];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$attributes = ['value' => $id];
			if ($page->routeId == $id) {
				$attributes['selected'] = 'selected';
			}
			$routeIdSelectOptions[$option] = $attributes;
		}

		$form->addField('routeId', 'select')
			->label('Útvonal')
			->options($routeIdSelectOptions);

		// Created At
		$form->addField('createdAt')
			->attribute('value', $page->createdAt)
			->label('CA');

		$form
			->lv('availableLanguages', $avaliableLanguages)
			->finalize();
		
		$this->lv('form', $form);
		$this->lv('upUrl', Router::getNoQueryStringUrl());
	}


	public function page_deleteAction()
	{
		$id = Router::get('id');
		$page = $this->model->getPageById($id);

		if ($page) {
			$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_delete']);
			$form
				->actionUrl(Router::getNoQueryStringUrl())
				->addField('id')
					->attributes(['value' => $id, 'readonly' => true])
					->label('ID');

			$form->finalize();
			$this->lv('form', $form);
		}
	}
}
