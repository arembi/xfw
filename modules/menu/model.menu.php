<?php

namespace Arembi\Xfw\Module;
use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Menu;
use Arembi\Xfw\Core\Models\Menuitem;

class MenuBaseModel {

	public function getMenuByMenuId(int $menuId)
	{
		$menu = Menu::with(['menuitems', 'domains'])->find($menuId);

		return $menu;
	}

	public function getMenuByMenuName(string $menuName, $domainId = null)
	{
		if ($domainId === null) {
			$domainId = DOMAIN_ID;
		}

		$menu = DB::table('menus')
			->join('menu_domain', 'menus.id', '=', 'menu_domain.menu_id')
			->select(
				'menus.id as id',
				'menus.name as name',
				'menus.type',
				'menu_domain.domain_id as domainId'
			)
			->where([
				['menus.name', $menuName],
				['menu_domain.domain_id', $domainId]
			])
			->first();

			$menu->menuitems = Menuitem::where('menu_id', $menu->id)
				->get()
				->map(function ($item){
					return $item['item'];
				})
				->toArray();

			return $menu;
	}


	public function getMenusByDomainId($domainId = null)
	{
		if ($domainId === null) {
			$domainId = DOMAIN_ID;
		}

		$menus = DB::table('menus')
			->join('menu_domain', 'menu_domain.menu_id', '=', 'menus.id')
			->select(
				'menus.id as id',
				'menus.name as name',
				'menus.type as type',
				'menus.created_at as createdAt',
				'menus.updated_at as updatedAt', 
				'menu_domain.domain_id as domainId'
			)
			->where('menu_domain.domain_id', $domainId)
			->get();
		return $menus;
	}

}
