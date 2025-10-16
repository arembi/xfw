<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;


class Control_Panel_MenuBase extends ModuleBase {

	protected static $hasModel = false;

    public function init()
	{
        $lang = App::getLang();
		$activeModules = App::getActiveModules('name');
		
		$menuItems = [];

		// Loading controller modules
		foreach ($activeModules as $module) {
			if (App::loadModuleAddon($module, 'cp') === true) {
				$addon = 'Arembi\Xfw\Module\CP_' . $module;
				$cpMenu = $addon::menu();

				// Module integration to CP menu
				if (!empty($cpMenu)) {
					$addonMenuData = [
						'displayTitle' => true,
						'title' => $cpMenu['title'][$lang] ?? array_values($cpMenu['title'])[0] ?? $cpMenu,
						'autoFinalize' => true
					];

					foreach ($cpMenu['items'] as $item) {
						$addonMenuData['items'][] = new Link([
							'anchor' => $item[1][$lang] ?? array_values($item[1])[0] ?? $item[1],
							'href' => '+route=' . Router::getMatchedRouteId() . '+module=' . $module . '?task=' . $item[0],
							'autoFinalize'=>true
						]);
					}

					$menuItems[] = new Menu($addonMenuData);
				}
			}
		}

        $this->lv('cpMenuItems', $menuItems);
	}
}