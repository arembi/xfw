<?php
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Core\Models\Domain;
use Arembi\Xfw\Core\Models\Route;

class Control_PanelBaseModel {

	
	public function newDomain(array $data)
	{
		// Input check and default values
		if (empty($data['domain']) || collect(Router::getDomains())->contains('domain', $data['domain'])) {
			return false;
		}
		if (empty($data['protocol'])) {
			$data['protocol'] = 'http://';
		}
		if (!isset($data['settings']) || !is_string($data['settings'])) {
			$data['settings'] = null;
		}

		$d = new Domain();
		$d->domain = $data['domain'];
		$d->protocol = $data['protocol'];
		$d->settings = $data['settings'];

		$result = $d->save();

		if ($result) {
			Router::loadDomains();
		}

		return $result;
	}


	public function updateDomain(array $data)
	{
		// Input check and default values
		if (empty($data['id'])) {
			return false;
		} else {
			$data['id'] = (int) $data['id'];
		}
		if (empty($data['domain']) || !is_string($data['domain'])) {
			return false;
		}
		if (empty($data['protocol']) || !in_array($data['protocol'], ['http://', 'https://'])) {
			return false;
		}
		if (!isset($data['settings']) || !is_string($data['settings'])) {
			$data['settings'] = null;
		}
		
		$d = Domain::find($data['id']);
		
		if ($d) {
			if ($d->domain === $data['domain'] || !collect(Router::getDomains())->contains('domain', $data['domain'])) {
				$d->domain = $data['domain'];
				$d->protocol = $data['protocol'];
				$d->settings = $data['settings'];
				
				$result = $d->save();
			} else {
				$result = false;
			}
		} else {
			$result = false;
		}

		if ($result) {
			Router::loadDomains();
		}

		return $result;
	}


	public function deleteDomain(int $id)
	{
		$result = false;
		$domain = Domain::find($id);
		
		if ($domain) {
			$result = $domain->delete();
			if ($result) {
				Router::loadDomains();
			}
		}

		return $result;
	}


	public function newRoute(array $data)
	{
		// Input check and default values
		if (empty($data['path']) || !is_array($data['path'])) {
			return false;
		}
		if (!isset($data['domainId']) || !is_numeric($data['domainId'])) {
			$data['domainId'] = DOMAIN_ID;
		}
		if (!isset($data['moduleId']) || !is_numeric($data['moduleId'])) {
			$data['moduleId'] = App::moduleInfo('name', 'static_page')->id;
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
		
		$newOK = true;
		$newPath = [];

		// Route
		// Checking whether there are already taken routes
		$nofAvLangs = count($avLangs);
		$nofTakenRoutes = count($takenRoutes);

		$i = 0;
		$j = 0;

		while ($newOK && $i < $nofAvLangs) {

			if (!empty($data['path'][$avLangs[$i][0]])) {
				$newPath[$avLangs[$i][0]] = $data['path'][$avLangs[$i][0]];
				if ($newPath[$avLangs[$i][0]] !== '/') {
					// Adding the required '/' to the beginning of the route if missing
					if (strpos($newPath[$avLangs[$i][0]], '/') !== 0) {
						$newPath[$avLangs[$i][0]] = '/' . $newPath[$avLangs[$i][0]];
					}

					// Removing slashes from the end of the URL
					$newPath[$avLangs[$i][0]] = rtrim($newPath[$avLangs[$i][0]], '/');
				}

				while ($j < $nofTakenRoutes && $newOK) {
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
			$r->domain_id = $data['domainId'];
			$r->path = $newPath;
			$r->module_id = $data['moduleId'];
			$r->module_config = $data['moduleConfig'];
			$r->clearance_level = $data['clearanceLevel'];
			/*$route = Route::create([
				'domain_id'=>$data['domainId'],
				'path'=>$newPath,
				'module_id'=>$data['moduleId'],
				'module_config'=>$data['moduleConfig'],
				'clearance_level'=>$data['clearanceLevel']
			]);*/

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
		if (!isset($data['id'], $data['path']) || empty($data['path']) || !is_array($data['path'])) {
			return false;
		} else {
			$data['id'] = (int) $data['id'];
		}

		if (!isset($data['domainId']) || !is_numeric($data['domainId'])) {
			$data['domainId'] = DOMAIN_ID;
		} else {
			$data['domainId'] = (int) $data['domainId'];
		}

		if (!isset($data['moduleId']) || !is_numeric($data['moduleId'])) {
			return false;
		} else {
			$data['moduleId'] = (int) $data['moduleId'];
		}

		if (!isset($data['clearanceLevel']) || !is_numeric($data['clearanceLevel'])) {
			return false;
		} else {
			$data['clearanceLevel'] = (int) $data['clearanceLevel'];
		}

		if (!isset($data['moduleConfig']) || !is_string($data['moduleConfig'])) {
			$data['moduleConfig'] = null;
		}

		$avLangs = Settings::get('availableLanguages');

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

		$nofAvLangs = count($avLangs);
		$nofTakenRoutes = count($takenRoutes);

		$updateOK = true;
		$updatedPath = [];

		// Route
		// Checking whether there are already taken routes
		$i = 0;
		$j = 0;

		while ($updateOK && $i < $nofAvLangs) {
			if (!empty($data['path'][$avLangs[$i][0]])) {

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
				while ($j < $nofTakenRoutes && $updateOK) {
					if ($takenRoutes[$j]->id !== $data['id']
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
			$route = Route::find($data['id']);
			$route->domain_id = $data['domainId'];
			$route->path = $updatedPath;
			$route->module_id = $data['moduleId'];
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



	public function deleteRoute(int $id)
	{
		$result = false;
		$route = Route::find($id);
		
		if ($route) {
			$result = $route->delete();
			if ($result) {
				Router::loadData();
			}
		}

		return $result;
	}

}
