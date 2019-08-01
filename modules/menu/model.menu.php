<?php

namespace Arembi\Xfw\Module;
use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Misc;
use Arembi\Xfw\Core\Models\Menu;
use Arembi\Xfw\Core\Models\Menuitem;

class MenuBaseModel {

	public function getMenuByMenuID(int $menuID)
	{
		$menu = Menu::with(['menuitems', 'domains'])->find($menuID);
		$menu->menuitems = $menu->menuitems
			->map(function ($item) {
				return $item['item'];
			})
			->toArray();

		return $menu;
	}

	public function getMenuByMenuName(string $menuName, $domainID = null)
	{
		if ($domainID === null) {
			$domainID = DOMAIN_ID;
		}

		$menu = DB::table('menus')
			->join('menu_domain', 'menus.id', '=', 'menu_domain.menu_id')
			->select(
				'menus.id as ID',
				'menus.name as name',
				'menus.type',
				'menu_domain.domain_id as domainID'
			)
			->where([
				['menus.name', $menuName],
				['menu_domain.domain_id', $domainID]
			])
			->first();

			//$menu->menuitems = Menuitem::where('menu_id', $menu->ID)->get()->toArray();
			$menu->menuitems = Menuitem::where('menu_id', $menu->ID)
				->get()
				->map(function ($item){
					return $item['item'];
				})
				->toArray();

			return $menu;
	}

}
