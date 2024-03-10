<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Domain;

class AppModel {

	/*
	 * Returns the modules that are enabled to the given site (domain)
	 * @param $domain
	 * 	the sites domain
	 * @param $active
	 * 	filters the modules to only activated ones
	 * */
	public function getInstalledModules($domain = null, $active = false)
	{
		if ($domain === null) {
			$domain = DOMAIN;
		}

		$installedModules = DB::table('modules')
			->leftJoin('module_domain', 'modules.id', '=', 'module_domain.module_id' )
			->leftJoin('domains', 'domains.id', '=', 'module_domain.domain_id' )
			->leftJoin('module_module_category', 'modules.id', '=', 'module_module_category.module_id' )
			->leftJoin('module_categories', 'module_module_category.module_category_id', '=', 'module_categories.id' )
			->select(
				'modules.id as ID',
				'modules.name as name',
				'modules.class as class',
				'modules.priority as priority',
				'module_domain.active as active',
				'modules.path_param_order as pathParamOrder',
				'module_categories.name as category'
				)
			->where('domains.domain', $domain)
			->orWhere('module_categories.name', 'core')
			->distinct()
			->get()
			->transform(function ($item, $key) {
				$item->pathParamOrder = json_decode($item->pathParamOrder ?? '', true);
				return $item;
			});

			if ($active) {
				$installedModules->filter(function($module){
					return $module->active == 1 ;
				});
			}
		return $installedModules->toArray();
	}


	public function getUsers()
	{
		$users = DB::table('users')
			->join('user_domain_user_group', 'users.id', '=', 'user_domain_user_group.user_id')
			->join('user_groups', 'user_domain_user_group.user_group_id', '=', 'user_groups.id')
			->join('domains', 'user_domain_user_group.domain_id', '=', 'domains.id')
			->select(
				'users.id as ID',
				'users.username as username',
				'users.first_name as firstName',
				'users.last_name as lastName',
				'users.email as email',
				'users.phone as phone',
				'users.address as address',
				'users.default_language_id as defaultLanguageID',
				'users.password as password',
				'user_groups.name as userGroup',
				'user_groups.clearance_level as clearanceLevel'
				)
			->get();

		return $users;
	}


	public function getUsersByDomain(string $domain = 'current')
	{
		if ($domain == 'current') {
			$domain = DOMAIN;
		}

		$users = DB::table('users')
			->join('user_domain_user_group', 'users.id', '=', 'user_domain_user_group.user_id')
			->join('user_groups', 'user_domain_user_group.user_group_id', '=', 'user_groups.id')
			->join('domains', 'user_domain_user_group.domain_id', '=', 'domains.id')
			->select(
				'users.id as ID',
				'users.username as username',
				'users.first_name as firstName',
				'users.last_name as lastName',
				'users.email as email',
				'users.phone as phone',
				'users.address as address',
				'users.default_language_id as defaultLanguageID',
				'users.password as password',
				'user_groups.name as userGroup',
				'user_groups.clearance_level as clearanceLevel'
				)
			->where('domains.domain', $domain)
			->get();

		return $users;
	}
}
