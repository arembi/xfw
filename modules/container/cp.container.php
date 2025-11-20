<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class CP_ContainerBase extends Container {

	public static function menu()
	{
		return [
			'title' => [
				'hu' => 'HTML tartalmak',
				'en' => 'HTML Content'
			],
			'items' => [
				['home', [
					'hu' => 'Info',
					'en' => 'Info']
				],
				['content_list', [
					'hu' => 'Lista',
					'en' => 'List']
				],
				['content_new', [
					'hu' => 'Új',
					'en' => 'New']
				]
			]
		];
	}

	public function homeAction()
	{
		$welcomeMessage = [
			'en'=>'Custom HTML code pieces, with a title.',
			'hu'=>'Tetszőleges HTML kód, címmel.'
		];
		$this->lv('welcomeMessage', $welcomeMessage);
	}



	public function content_listAction()
	{
		$contents = $this->model->getContents();
		$avaliableLanguages = Settings::get('availableLanguages');
		
		foreach ($contents as &$content) {
			$content->editLink = new Link(['anchor' => 'edit', 'href' => '?task=content_edit&id=' . $content->id, 'autoFinalize'=>true]);
			$content->deleteLink = new Link(['anchor' => 'delete', 'href' => '?task=content_delete&id=' . $content->id, 'autoFinalize'=>true]);
		}
		unset($content);

		$this
			->lv('availableLanguages', $avaliableLanguages)
			->lv('contents', $contents);

	}



	public function content_newAction()
	{
		$form = new Form(['handlerModule' => 'container', 'handlerMethod' => 'content_new']);
		$avaliableLanguages = Settings::get('availableLanguages');

		foreach ($avaliableLanguages as $lang) {
			$form->addField('title-' . $lang[0])
				->label('Title (' . $lang[0] . ')');
		}
		foreach ($avaliableLanguages as $lang) {
			$form->addField('content-' . $lang[0], 'textarea')
				->label('Content (' . $lang[0] . ')');
		}

		$form
			->lv('availableLanguages', $avaliableLanguages)
			->finalize();

		$this
			->lv('availableLanguages', $avaliableLanguages)
			->lv('form', $form);
	}



	public function content_editAction()
	{
		$content = $this->model->getContentById(Router::get('id'));
		$avaliableLanguages = Settings::get('availableLanguages');

		$form = new Form(['handlerModule' => 'container', 'handlerMethod' => 'content_update']);
		$form
			->layout('content_edit')
			->layoutVariant('content_edit');

		// ID
		$form->addField('id')
			->attributes(['value' => $content->id, 'readonly' => true])
			->label('ID');

		// Content Title
		foreach ($avaliableLanguages as $lang) {
			$contentTitle = $content->title[$lang[0]] ?? '';
			
			$form->addField('title-' . $lang[0])
				->label('Title (' . $lang[0] . ')')
				->attribute('value', $contentTitle);
		}

		// Content
		foreach ($avaliableLanguages as $lang) {
			$contentContent = $content->content[$lang[0]] ?? '';
			
			$form->addField('content-' . $lang[0], 'textarea')
				->label('Content (' . $lang[0] . ')')
				->text($contentContent);
		}

		// Created At
		$form->addField('createdAt')
			->attribute('value', $content->created_at)
			->label('CA');

		$form
			->lv('availableLanguages', $avaliableLanguages)
			->finalize();

		$this
			->lv('availableLanguages', $avaliableLanguages)
			->lv('upUrl', Router::url('+route=' . Router::getMatchedRoute()->id))
			->lv('form', $form);
	}


	public function content_deleteAction()
	{
		$id = Router::get('id');
		$content = $this->model->getContentById($id);

		if ($content) {
			$form = new Form(['handlerModule' => 'container', 'handlerMethod' => 'content_delete']);
		
			$form->addField('id')
				->attributes(['value' => $id, 'readonly' => true])
				->label('ID');

			$form->finalize();
			$this->lv('form', $form);
		}
	}
}
