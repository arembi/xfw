<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Seo;

/*
URL path parameters
	- module
	- task
/static_page/new
/blog_post/edit?id=123

The module can be either a regular module, or the control panel itself
The control panel handles tasks that belong to the system, like
	- system configuration
	- site composition routes
	- module management (install, update, remove)
The regular modules have to be able to handle the tasks given to them within
their own operating area
*/


class Control_PanelBase extends \Arembi\Xfw\Core\ModuleCore {

	public function main()
	{
		$this->loadPathParams();

		// Executing default action
		if (Router::getMatchedRouteAction() === null) {
			$this->panelAction();
		}
	}


	public function panelAction()
	{
		$lang = App::getLang();

		$cpMenuItems = [];

		// Loading controller modules
		foreach (App::getActiveModules('name') as $module) {
			if (App::loadModuleAddon($module, 'cp') === true) {
				$addon = 'Arembi\Xfw\Module\CP_' . $module;
				$cpMenu = $addon::menu();

				// Module integration to CP menu
				if (!empty($cpMenu)) {
					$addonMenuData = [
						'showTitle' => true,
						'title' => $cpMenu['title'][$lang] ?? array_values($cpMenu['title'])[0] ?? $cpMenu,
					];

					foreach ($cpMenu['items'] as $item) {
						$addonMenuData['items'][] = [
							'anchorText' => $item[1][$lang] ?? array_values($item[1])[0] ?? $item[1],
							'href' => '+route=' . Router::getMatchedRouteId() . '+module=' . $module . '?task=' . $item[0]
						];
					}

					$cpMenuItems[] = new Menu($addonMenuData);
				}
			}
		}

		$module = $this->options['module'] ?? 'control_panel';
		$task = Router::$GET['task'] ?? 'home';
		$controllerClass = 'Arembi\Xfw\\Module\\CP_' . $module;

		if (class_exists($controllerClass)) {
			$controller = new $controllerClass();
			if (method_exists($controller, $task)) {
				// The module addons use the same model as the main modules
				$controller->loadModel();
				// The output of the module addons will be stored in $main
				ob_start();
				$controller->$task();
				$main = ob_get_clean();
			}
		}

		if (!isset($main)) {
			$main = '';
		}

		$this->lv('cpMenuItems', $cpMenuItems);
		$this->lv('main', $main);
		Seo::title($task . ' - Control Panel', __CLASS__);
		Seo::metaDescription($task . ' task of ' . $module . ' module', __CLASS__);
	}

}
