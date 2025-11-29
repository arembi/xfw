<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Domain;
use Arembi\Xfw\Core\Models\Link;
use Arembi\Xfw\Core\Models\Redirect;
use Arembi\Xfw\Core\Models\Route;

use function Arembi\Xfw\Misc\decodeIfJson;


class RouterModel {

	public function getDomains()
	{
		return Domain::all()->mapWithKeys(fn($d) => [$d['id'] => $d]);
	}


	public function getDomainById($id)
	{
		return Domain::find($id);
	}

	/* 
	 * Returns the saved links as an array where the keys are the link IDs
	 * specific domain
	 * 'this' refers to the current domain
	 * 'all'
	 */
	public function getSystemLinksByDomain(string|int $domain = 'this')
	{
		$links = DB::table('links')
			->join('routes', 'links.route_id', '=', 'routes.id')
			->join('domains', 'routes.domain_id', '=', 'domains.id')
			->join('modules', 'routes.module_id', '=', 'modules.id')
			->select(
				'links.id as id',
				'links.lang as lang',
				'links.path_params as pathParameters',
				'links.query_params as queryParameters',
				'routes.id as routeId',
				'routes.path as path',
				'routes.module_config as moduleConfig',
				'domains.domain as domain',
				'domains.id as domainId',
				'modules.name as moduleName',
				'modules.path_param_order as ppo'
			)
			->get()
			->map(function($item){
				// JSON decoding the route
				$item->path = decodeIfJson($item->path, true);

				if (is_string($item->path) && $item->path != '/') {
					$item->path = [Settings::get('defaultLanguage') => $item->path];
				}

				$item->pathParameters = json_decode($item->pathParameters ?? '', true);
				$item->queryParameters = json_decode($item->queryParameters ?? '', true);
				$item->moduleConfig = json_decode($item->moduleConfig ?? '', true);

				$item->ppo = $item->moduleConfig->ppo
					?? json_decode($item->ppo ?? '', true)
					?? [];

				return $item;
			});
		
		if ($domain !== 'all') {
			if ($domain == 'this') {
				$domain = DOMAIN_ID;
			}
			$links = $links->filter(function($value, $key) use ($domain) {
				return $value->domainId == $domain;
			});
		}

		return $links->mapWithKeys(function($l) {
			$id = $l->id;
			unset($l->id);
			return [$id => $l];
		});
	}


	public function getAvailableRoutes(int $domainId = DOMAIN_ID)
	{
		$routes = DB::table('routes')
			->join('domains', 'routes.domain_id', '=', 'domains.id')
			->join('modules', 'routes.module_id', '=', 'modules.id')
			->select(
				'routes.id as id',
				'routes.path as path',
				'routes.domain_id as domainId',
				'routes.clearance_level as clearanceLevel',
				'routes.module_config as moduleConfig',
				'modules.name as moduleName',
				'modules.class as moduleClass',
				'modules.path_param_order as modulePpo'
			)
			->where('domains.id', $domainId)
			->whereIn('modules.class', ['p', 'b'])
			->get()
			->transform(function($item){
				$item->path = decodeIfJson($item->path, true);

				if (is_string($item->path) && $item->path != '/') {
					$item->path = [Settings::get('defaultLanguage') => $item->path];
				}

				$item->modulePpo = json_decode($item->modulePpo ?? '', true);

				$item->moduleConfig = json_decode($item->moduleConfig ?? '', true);

				return $item;
			});

		return $routes;
	}


	public function getRouteById(int $routeId)
	{
		$route = Route::find($routeId);

		if ($route) {
			// If the route is a string instead of an array, we use it with the default language
			// There is no point to store the root for every language, so it will be left the way it was
			if (is_string($route->path) && $route->path != '/') {
				$route->path = [Settings::get('defaultLanguage') => $route->path];
			}
		}
		return $route;
	}


	public function getLinkById(int $linkId)
	{
		return Link::find($linkId);
	}
}
