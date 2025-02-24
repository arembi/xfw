<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\ModuleCore;


class Control_Panel_MenuBase extends ModuleCore {

	protected static $hasModel = false;

    public function main()
	{
        $lang = App::getLang();

		$menuItems = [];

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

					$menuItems[] = new Menu($addonMenuData);
				}
			}
		}

        $this->lv('cpMenuItems', $menuItems);
	}

    
}