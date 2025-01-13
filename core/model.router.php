<?php

namespace Arembi\Xfw\Core;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\Models\Domain;
use Arembi\Xfw\Core\Models\Redirect;
use Arembi\Xfw\Core\Models\Route;
use Arembi\Xfw\Misc;

class RouterModel {

	public function getDomains()
	{
		$domains = [];

		$temp = Domain::all()->toArray();

		foreach ($temp as $d) {
			$id = $d['id'];
			unset($d['id']);
			$domains[$id] = $d;
		}

		return $domains;
	}


	public function getDomainById($id)
	{
		return Domain::find($id);
	}


	// Returns the saved links as an array where the keys are the link IDs
	/*
	 * @param domain:
	 * you can use a specific domain
	 * 'this' refers to the current domain
	 * to get access to all domains, use 'all'
	 * */
	public function getSystemLinks($domain = 'this')
	{
		// Links to return
		$rlinks = [];

		$links = DB::table('links')
			->join('routes', 'links.route_id', '=', 'routes.id')
			->join('domains', 'routes.domain_id', '=', 'domains.id')
			->join('modules', 'routes.module_id', '=', 'modules.id')
			->select(
				'links.id as linkId',
				'links.lang as linkLang',
				'links.path_params as pathParams',
				'links.query_string as queryString',
				'routes.id as routeId',
				'routes.path as path',
				'routes.module_config as moduleConfig',
				'domains.domain as domain',
				'domains.id as domainId',
				'modules.name as moduleName',
				'modules.path_param_order as ppo'
				)
			->get()
			->map(function($item, $key) use ($rlinks){
				// JSON decoding the route
				$item->path = Misc\decodeIfJSON($item->path, true);

				if (is_string($item->path) && $item->path != '/') {
					$item->path = [Settings::get('defaultLanguage') => $item->path];
				}

				$item->pathParams = json_decode($item->pathParams ?? '', true);
				$item->moduleConfig = json_decode($item->moduleConfig ?? '', true);

				$item->ppo = $item->moduleConfig->ppo
					?? json_decode($item->ppo ?? '', true)
					?? [];

				// JSON decoding the query string
				$item->queryString = Misc\decodeIfJSON($item->queryString, true);

				// If the string was directly given, we convert it to an array
				if (is_string($item->queryString)) {

					// Removing questionmark if present
					$qs = ltrim($item->queryString, '?');

					$item->queryString = [];
					// Converting to array
					parse_str($qs, $item->queryString);

				} elseif($item->queryString === null) {
					$item->queryString = [];
				}

				return $item;
			});

		if ($domain !== 'all') {
			if ($domain == 'this') {
				$domain = DOMAIN;
			}
			$links = $links->filter(function($value, $key) use ($domain){
				return $value->domain == $domain;
			});
		}

		foreach ($links as $link) {
			$id = $link->linkId;
			unset($link->linkId);
			$rlinks[$id] = (array) $link;
		}
		return $rlinks;
	}


	public function getAvailableRoutes($domain = DOMAIN)
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
			->where('domains.domain', $domain)
			->whereIn('modules.class', ['p', 'b'])
			->get()
			->transform(function($item, $key){
				$item->path = Misc\decodeIfJSON($item->path, true);

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


	public function getRedirects()
	{
		// Load hard coded redirects
		$hcRedirects = Config::_('redirects', []);

		// Load redirects from the database
		$dbRedirects = Redirect::all()
			->transform(function($item, $key){
				$domain = $item->domain;
				if ($domain) {
					$item->domain = $domain->domain;
				}
				return $item;
			})->toArray();

		$redirects = array_merge($hcRedirects, $dbRedirects);

		return $redirects;
	}
}
