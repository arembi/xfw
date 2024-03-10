<?php
namespace Arembi\Xfw\Module;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Core\Models\Route;

class Control_PanelBaseModel {

	public function newRoute(array $data)
	{
		// Input check and default values
		if (!isset($data['path']) || empty($data['path']) || !is_array($data['path'])) {
			return false;
		}
		if (!isset($data['domainID']) || !is_numeric($data['domainID'])) {
			$data['domainID'] = DOMAIN_ID;
		}
		if (!isset($data['moduleID']) || !is_numeric($data['moduleID'])) {
			$data['moduleID'] = App::moduleInfo('name', 'static_page')->ID;
		}
		if (!isset($data['clearanceLevel']) || !is_numeric($data['clearanceLevel'])) {
			$data['clearanceLevel'] = 0;
		}
		if (!isset($data['moduleConfig']) || !is_string($data['moduleConfig'])) {
			$data['moduleConfig'] = null;
		}

		$avLangs = Settings::get('availableLanguages');
		$takenRoutes = Router::getRoutes()
			->pluck('path')
			->map(function ($path) use ($avLangs){
				if ($path == '/') {
					foreach ($avLangs as $lang) {
						$path = [];
						$path[$lang[0]] = '/';
					}
				}
				return $path;
			})
			->toArray();

		$newHasRoutes = false;
		$newOK = true;
		$newPath = [];

		// Route
		// Checking whether there are already taken routes
		$i = 0;
		$j = 0;
		$l = count($avLangs);

		while ($newOK && $i < $l) {
			if (!empty($data['path'][$avLangs[$i][0]])) {
				$newHasRoutes = true;

				$newPath[$avLangs[$i][0]] = $data['path'][$avLangs[$i][0]];
				if ($newPath[$avLangs[$i][0]] !== '/') {
					// Adding the required '/' to the beginning of the route if missing
					if (strpos($newPath[$avLangs[$i][0]], '/') !== 0) {
						$newPath[$avLangs[$i][0]] = '/' . $newPath[$avLangs[$i][0]];
					}

					// Removing slashes from the end of the URL
					if ($newPath[$avLangs[$i][0]] !== '/') {
						$newPath[$avLangs[$i][0]] = rtrim($newPath[$avLangs[$i][0]], '/');
					}
				}

				while ($j < count($takenRoutes) && $newOK) {
					if (isset($takenRoutes[$j][$avLangs[$i][0]])
					&& $takenRoutes[$j][$avLangs[$i][0]] == $newPath[$avLangs[$i][0]]) {
						$newOK = false;
					}
					$j++;
				}
			}
			$i++;
		}

		if ($newOK) {
			$r = new Route();
			$r->domain_id = $data['domainID'];
			$r->path = $newPath;
			$r->module_id = $data['moduleID'];
			$r->module_config = $data['moduleConfig'];
			$r->clearance_level = $data['clearanceLevel'];

			$result = $r->save();

			if ($result) {
				Router::loadData();
			}
		} else {
			$result = false;
		}
		return $result;
	}



	public function updateRoute(array $data)
	{
		// Input check and default values
		if (!isset($data['ID'], $data['path']) || empty($data['path']) || !is_array($data['path'])) {
			return false;
		} else {
			$data['ID'] = (int) $data['ID'];
		}

		if (!isset($data['domainID']) || !is_numeric($data['domainID'])) {
			$data['domainID'] = DOMAIN_ID;
		} else {
			$data['domainID'] = (int) $data['domainID'];
		}

		if (!isset($data['moduleID']) || !is_numeric($data['moduleID'])) {
			return false;
		} else {
			$data['moduleID'] = (int) $data['moduleID'];
		}

		if (!isset($data['clearanceLevel']) || !is_numeric($data['clearanceLevel'])) {
			return false;
		} else {
			$data['clearanceLevel'] = (int) $data['clearanceLevel'];
		}

		if (!isset($data['moduleConfig']) || !is_string($data['moduleConfig'])) {
			$data['moduleConfig'] = null;
		}

		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		$la = count($avLangs);

		$takenRoutes = Router::getRoutes()
			->map(function ($route) use ($avLangs){
				if ($route->path == '/') {
					foreach ($avLangs as $lang) {
						$route->path = [];
						$route->path[$lang[0]] = '/';
					}
				}
				return $route;
			})
			->all();

		$lt = count($takenRoutes);

		$updateHasRoutes = false;

		$updateOK = true;

		$updatedPath = [];

		// Route
		// Checking whether there are already taken routes
		$i = 0;
		$j = 0;

		while ($updateOK && $i < $la) {
			if (!empty($data['path'][$avLangs[$i][0]])) {
				$updateHasRoutes = true;

				// Adding the required '/' to the beginning of the route if missing
				$updatedPath[$avLangs[$i][0]] = $data['path'][$avLangs[$i][0]];
				if (strpos($updatedPath[$avLangs[$i][0]], '/') !== 0) {
					$updatedPath[$avLangs[$i][0]] = '/' . $updatedPath[$avLangs[$i][0]];
				}

				// Removing slashes from the end of the URL
				if ($updatedPath[$avLangs[$i][0]] !== '/') {
					$updatedPath[$avLangs[$i][0]] = rtrim($updatedPath[$avLangs[$i][0]], '/');
				}

				// Only have to check the other routes, the same routes path can be changed
				while ($j < $lt && $updateOK) {
					if ($takenRoutes[$j]->ID !== $data['ID']
						&& isset($takenRoutes[$j]->path[$avLangs[$i][0]])
						&& $takenRoutes[$j]->path[$avLangs[$i][0]] == $updatedPath[$avLangs[$i][0]]) {
						$updateOK = false;
					}
					$j++;
				}
			}
			$i++;
		}

		if ($updateOK) {
			$route = Route::find($data['ID']);
			$route->domain_id = $data['domainID'];
			$route->path = $updatedPath;
			$route->module_id = $data['moduleID'];
			$route->module_config = $data['moduleConfig'];
			$route->clearance_level = $data['clearanceLevel'];

			$result = $route->save();

			if ($result) {
				Router::loadData();
			}
		} else {
			$result = false;
		}
		return $result;
	}



	public function deleteRoute($id)
	{
		$route = Route::find($id);
		$result = $route->delete();

		if ($result) {
			Router::loadData ();
		} else {
			$result = false;
		}
		return $result;
	}

}
