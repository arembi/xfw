<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Inc\FormField;

class CP_Control_PanelBase extends Control_panel {

	public static function menu()
	{
		return [
			'title'=>[
				'hu'=>'Rendszer',
				'en'=>'System'
			],
			'items'=>[
				['home', [
					'hu'=>'Kezdőlap',
					'en'=>'Dash']
				],
				['domain_list', [
					'hu'=>'Domain lista',
					'en'=>'Domain List']
				],
				['domain_new', [
					'hu'=>'Új domain',
					'en'=>'New Domain']
				],
				['route_list', [
					'hu'=>'Útvonal lista',
					'en'=>'Route List']
				],
				['route_new', [
					'hu'=>'Új útvonal',
					'en'=>'New Route']
				]
			]
		];
	}
	

	public function homeAction()
	{
		$welcomeMessage = 'Control panel home page';
		$this->lv('heading', $welcomeMessage);
	}


	public function domain_listAction()
	{
		$domains = Router::getDomains();
		
		foreach ($domains as $id => &$domainData) {
			$editLink = new Link(['anchor'=>'edit', 'href'=>'?task=domain_edit&id=' . $id, 'autoFinalize'=>true]);
			$deleteLink = new Link(['anchor'=>'delete', 'href'=>'?task=domain_delete&id=' . $id, 'autoFinalize'=>true]);

			$domainData['editLink'] = $editLink->processLayout()->getLayoutHtml();
			$domainData['deleteLink'] = $deleteLink->processLayout()->getLayoutHtml();
		}

		unset($domainData);

		$this->lv('domains', $domains);
	}


	public function domain_newAction()
	{
		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'domain_new']);

		$form->actionUrl('?task=domain_list');

		$form->addField('domain')
			->label('Domain');
		
		$protocolSelectOptions = [
			'http://'=>['value'=>'http://'],
			'https://'=>['value'=>'https://']
		];
		$form->addField('protocol', 'select')
			->options($protocolSelectOptions)
			->label('Protocol');

		$form->addField('settings', 'textarea')
			->label('Domain Settings (JSON)');

		$form->addField('test', 'file')
			->label('Test');

		$form->finalize();

		$this->lv('form', $form);
	}


	public function domain_editAction()
	{	
		$domainId = Router::request('id');
		$domain = Router::getDomainRecordById($domainId);
		
		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'domain_edit']);

		// ID
		$form->addField('domainId')
			->attributes(['value'=>$domainId, 'readonly'=>true])
			->label('ID');

		// Domain name
		$form->addField('domain')
			->label('Domain Name')
			->attribute('value', $domain['domain']);

		// Protocol
		$protocolSelectOptions = [
			'http://'=>['value'=>'http://'],
			'https://'=>['value'=>'https://']
		];
		
		$protocolSelectOptions[$domain['protocol']]['selected'] = 'selected';

		$form->addField('protocol', 'select')
			->options($protocolSelectOptions)
			->label('Protocol');
		
		// Domain Settings
		if (!empty($domain['settings'])) {
			$domainSettingsField = $form->addFieldSet('domainSettings')
				->label('Domain Settings');
		
			foreach ($domain['settings'] as $k => $s) {
				$newField = new FormField();
				$newField
					->type('textarea')
					->label($k)
					->text(is_string($s) ? $s : json_encode($s,JSON_UNESCAPED_UNICODE));

				$domainSettingsField->addField($domainSettingsField->name() . '[' . $k . ']', $newField);
			}
		} else {
			$form
				->addField('domainSettings', 'textarea')
				->label('Domain Settings (JSON)')
				->text('{}');
		}
		
		$form->finalize();

		$this->lv('form', $form);
	}


	public function domain_deleteAction()
	{
		$domainId = Router::request('id');
		$domain = Router::getDomainRecordById($domainId);

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'domain_delete']);

		// ID
		$form->addField('domainId')
			->attributes(['value'=>$domainId, 'readonly'=>true])
			->label('ID');
		
		$form->lv('id', $domainId);
		$form->lv('domain', $domain['domain']);

		$form->finalize();

		$this->lv('form', $form);
	}


	public function route_listAction()
	{
		$routes = Router::getRoutes('primary');
		
		foreach ($routes as $id=>&$routeData) {
			if (is_array($routeData->path)) {
				$cell = '';
				foreach ($routeData->path as $lang=>$path) {
					$cell .= $lang . ': ' . $path . '<br>';
				}
				$routeData->pathLabel = $cell;
			} else {
				$routeData->pathLabel = Settings::get('defaultLanguage') . ': ' . $routeData->path;
			}

		$editLink = new Link(['anchor'=>'edit', 'href'=>'?task=route_edit&id=' . $id, 'autoFinalize'=>true]);
		$routeData->editLink = $editLink->processLayout()->getLayoutHtml();
		$deleteLink = new Link(['anchor'=>'delete', 'href'=>'?task=route_delete&id=' . $id, 'autoFinalize'=>true]);
		$routeData->deleteLink = $deleteLink->processLayout()->getLayoutHtml();

		}
		unset($routeData);

		$this->lv('routes', $routes);
	}


	public function route_newAction()
	{
		$modules = App::getPrimaryModules();
		$avLangs = Settings::get('availableLanguages');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_new']);

		$form->actionUrl('?task=route_list');

		foreach ($avLangs as $l) {
			$form
				->addField('path-' . $l[0])
				->label('Útvonal (' . $l[0] . ')');
		}

		$moduleSelectOptions = [];
		foreach($modules as $module){
			$moduleSelectOptions[$module->name] = ['value'=>$module->id];
		}
		$form->addField('moduleId', 'select')
			->options($moduleSelectOptions)
			->label('Modul');

		$form->addField('moduleConfig', 'textarea')
			->label('Modul config (JSON)');

		$form->addField('clearanceLevel')
			->label('Hozzáférési szint');

		$form->finalize();

		$this->lv('form', $form);
	}


	public function route_editAction()
	{
		// Loading route info
		$route = Router::getRouteRecordById(Router::request('id'));

		$modules = App::getPrimaryModules();

		$avLangs = Settings::get('availableLanguages');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_edit']);

		// ID
		$form->addField('routeId')
			->attributes(['value'=>$route->id, 'readonly'=>true])
			->label('ID');

		// Route
		if ($route->path === '/') {
			$route->path = [Settings::get('defaultLanguage')=>$route->path] ;
		}

		foreach ($avLangs as $l) {
			$pathField = $form->addField('path-' . $l[0]);
			$pathField->label('Útvonal (' . $l[0] . ')');
			
			if (isset($route->path[$l[0]])) {
				$pathField->attribute('value', $route->path[$l[0]]);
			}
		}

		// Module
		$moduleSelectOptions = [];
		foreach ($modules as $key=>$module) {
			$attributes = ['value'=>$module->id];
			if($route->moduleName == $module->name){
				$attributes['selected'] = 'selected';
				$selectedModuleId = $module->id;
			}
			$moduleSelectOptions[$module->name] = $attributes;
		}

		$form->addField('moduleId', 'select')
			->options($moduleSelectOptions)
			->label('Modul')
			->attribute('value', $selectedModuleId);
		
		// Module config
		if (!empty($route->moduleConfig)) {
			$moduleConfigField = $form->addFieldSet('moduleConfig')
				->label('Module config');
		
			foreach ($route->moduleConfig as $k => $c) {
				$newField = new FormField();
				$newField
					->type('textarea')
					->label($k)
					->text(is_string($c) ? $c : json_encode($c));

				$moduleConfigField->addField($moduleConfigField->name() . '[' . $k . ']', $newField);
			}
		} else {
			$form->addField('moduleConfig', 'textarea')
				->label('Module config (JSON)')
				->text('{}');
		}
		
		// Clearance level
		$form->addField('clearanceLevel')
			->label('Hozzáférési szint')
			->attribute('value', $route->clearanceLevel);

		$form->finalize();

		$this->lv('form', $form);
	}


	public function route_deleteAction()
	{
		$routeId = Router::request('id');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_delete']);
		
		$form->addField('routeId')
			->attributes(['value'=>$routeId, 'readonly'=>true])
			->label('ID');

		$form->finalize();

		$this->lv('form', $form);
	}
}
