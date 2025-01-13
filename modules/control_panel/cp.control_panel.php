<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\FormField;

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
	

	public function home()
	{
		?>
		<div style="font-weight:bold;font-size:2em;text-align:center;">
			Control panel home page
		</div>
		<?php
	}


	public function route_list()
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

		$editLink = new Link(['anchor'=>'edit', 'href'=>'?task=route_edit&id=' . $id]);
		$routeData->editLink = $editLink->processLayout()->getLayoutHTML();
		$deleteLink = new Link(['anchor'=>'delete', 'href'=>'?task=route_delete&id=' . $id]);
		$routeData->deleteLink = $deleteLink->processLayout()->getLayoutHTML();

		}

		unset($routeData);
		
		?>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Path</th>
					<th>Module</th>
					<th>Module Config</th>
					<th title="Clearance Level">CL</th>
					<th colspan="2">Tools</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($routes as $id=>$routeData):?>
				<tr>
					<td title="ID"><?php echo $routeData->id;?></td>
					<td title="path"><?php echo $routeData->pathLabel?></td>
					<td title="module name"><?php echo $routeData->moduleName?></td>
					<td title="module configuration for the route"><?php echo json_encode($routeData->moduleConfig)?></td>
					<td title="clearance level"><?php echo $routeData->clearanceLevel?></td>
					<td title="edit"><?php echo $routeData->editLink ?></td>
					<td title="delete"><?php echo $routeData->deleteLink ?></td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
		<?php
	}


	public function route_new()
	{
		$modules = App::getPrimaryModules();
		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_new'], false);

		$form->actionUrl('?task=route_list');

		foreach ($avLangs as $l) {
			$form->addField('path-' . $l[0])
				->label('Útvonal (' . $l[0] . ')');
		}

		$moduleSelectOptions = [];
		foreach($modules as $key=>$module){
			$moduleSelectOptions[$module->name] = ['value'=>$module->id];
		}
		$form->addField('moduleId', 'select')
			->options($moduleSelectOptions)
			->label('Modul');

		$form->addField('moduleConfig', 'textarea')
			->label('Modul config (JSON)');

		$form->addField('clearanceLevel')
			->label('Hozzáférési szint');

		$form
			->build()
			->processLayout()
			->render();
	}


	public function route_edit()
	{
		// Loading route info
		$route = Router::getRouteRecordById(Router::$REQUEST['id']);

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
				$newField->type('textarea')
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

		$form
			->build()
			->processLayout()
			->render();

	}


	public function route_delete()
	{
		$routeId = Router::$REQUEST['id'];

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_delete']);
		
		$form->addField('routeId')
			->attributes(['value'=>$routeId, 'readonly'=>true])
			->label('ID');

		$form
			->build()
			->processLayout()
			->render();

	}
}
