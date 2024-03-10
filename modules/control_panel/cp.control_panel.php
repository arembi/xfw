<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class CP_Control_PanelBase extends Control_panel {

	public static $cpMenu = [
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
	

	public function home()
	{?>
		<div>
			Control panel home page
		</div>
		<?php
	}


	public function route_list()
	{
		$routes = Router::getRoutes('primary');
		foreach ($routes as $ID=>&$routeData) {
			if (is_array($routeData->path)) {
				$cell = '';
				foreach ($routeData->path as $lang=>$path) {
					$cell .= $lang . ': ' . $path . '<br>';
				}
				$routeData->pathLabel = $cell;
			} else {
				$routeData->pathLabel = Settings::get('defaultLanguage') . ': ' . $routeData->path;
			}

		$editLink = new Link(['anchor'=>'edit', 'href'=>'?task=route_edit&id=' . $ID]);
		$routeData->editLink = $editLink->processLayout()->getLayoutHTML();
		$deleteLink = new Link(['anchor'=>'delete', 'href'=>'?task=route_delete&id=' . $ID]);
		$routeData->deleteLink = $deleteLink->processLayout()->getLayoutHTML();

		}
		unset($routeData); ?>
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
					<td title="ID"><?php echo $routeData->ID;?></td>
					<td title="path"><?php echo $routeData->pathLabel?></td>
					<td title="module name"><?php echo $routeData->moduleName?></td>
					<td title="module configuration for the route"><?php echo json_encode($routeData->moduleConfig)?></td>
					<td title="clearance level"><?php echo $routeData->clearanceLevel?></td>
					<td title="edit"><?php echo $routeData->editLink ?></td>
					<td title="delete"><?php echo $routeData->deleteLink ?></td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table><?php
	}


	public function route_new()
	{
		$modules = App::getPrimaryModules();
		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_new'], false);

		foreach ($avLangs as $l) {
			$form->addField('path-' . $l[0]);
			$form->setFieldLabel('path-' . $l[0], 'Útvonal (' . $l[0] . ')');
		}

		$moduleSelectOptions = [];
		foreach($modules as $key=>$module){
			$moduleSelectOptions[$module->name] = ['value'=>$module->ID];
		}
		$form->addField('moduleID', 'select');
		$form->setFieldOptions('moduleID', $moduleSelectOptions);
		$form->setFieldLabel('moduleID', 'Modul');

		$form->addField('moduleConfig', 'textarea');
		$form->setFieldLabel('moduleConfig', 'Modul config (JSON)');

		$form->addField('clearanceLevel');
		$form->setFieldLabel('clearanceLevel', 'Hozzáférési szint');

		$form->build();
		$form->render();
	}


	public function route_edit()
	{
		// Loading route info
		$route = Router::getRouteRecordByID(Router::$REQUEST['id']);

		$modules = App::getPrimaryModules();

		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_edit']);

		// ID
		$form->addField('routeID');
		$form->setFieldAttributes('routeID', ['value'=>$route->ID, 'readonly'=>true]);
		$form->setFieldLabel('routeID', 'ID');

		// Route
		if ($route->path === '/') {
			$route->path = [Settings::get('defaultLanguage')=>$route->path] ;
		}

		foreach ($avLangs as $l) {
			$form->addField('path-' . $l[0]);
			$form->setFieldLabel('path-' . $l[0], 'Útvonal (' . $l[0] . ')');
			if (isset($route->path[$l[0]])) {
				$form->setFieldAttribute('path-' . $l[0], 'value', $route->path[$l[0]]);
			}
		}

		// Module
		$moduleSelectOptions = [];
		foreach ($modules as $key=>$module) {
			$attributes = ['value'=>$module->ID];
			if($route->moduleName == $module->name){
				$attributes['selected'] = 'selected';
				$selectedModuleID = $module->ID;
			}
			$moduleSelectOptions[$module->name] = $attributes;
		}

		$form->addField('moduleID', 'select');
		$form->setFieldOptions('moduleID', $moduleSelectOptions);
		$form->setFieldLabel('moduleID', 'Modul');
		$form->setFieldAttribute('moduleID', 'value', $selectedModuleID);

		// Module config
		$form->addField('moduleConfig', 'textarea');
		$form->setFieldLabel('moduleConfig', 'Modul config (JSON)');
		$form->setFieldText('moduleConfig', json_encode($route->moduleConfig));

		// Clearance level
		$form->addField('clearanceLevel');
		$form->setFieldLabel('clearanceLevel', 'Hozzáférési szint');
		$form->setFieldAttribute('clearanceLevel', 'value', $route->clearanceLevel);

		$form->build();
		$form->render();
	}


	public function route_delete()
	{
		$routeID = Router::$GET['id'];

		$form = new Form(['handlerModule'=>'control_panel', 'handlerMethod'=>'route_delete']);
		$form->addField('routeID');
		$form->setFieldAttributes('routeID', ['value'=>$routeID, 'readonly'=>true]);
		$form->setFieldLabel('routeID', 'ID');

		$form->build();
		$form->render();

	}
}
