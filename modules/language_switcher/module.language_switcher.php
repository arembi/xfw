<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class Language_SwitcherBase extends ModuleBase {
  
	protected static $hasModel = false;

	protected function init()
	{
		if (!Settings::get('multiLang')) {
			$this->error('Site is not nultilingual, cannot load the Language Switcher.');
		}

		$availableLanguages = Settings::get('availableLanguages');
		
		$switcherMenu = new Menu([
			'layout'=>'default',
			'layoutVariant'=>'default',
			'title'=>[
				'en'=>'Languages',
				'hu'=>'Nyelvek'
			],
			'displayTitle'=>false
		]);
		
		foreach ($availableLanguages as $language) {
			$route = Router::getMatchedRoute();
			if (isset($route->path[$language[0]])) {
				$trailingSlash = Settings::get('URLTrailingSlash') == 'force' ? '/' : '';
				
				$routeString = $route->path[$language[0]] != '/' ? $route->path[$language[0]] : $trailingSlash;	
				
				$pathParams = Router::getPathParams();
				$pathParamsString = count($pathParams) > 0 ? '/' . implode('/', Router::getPathParams()) : '';
				
				if (!$pathParamsString) {
					$routeString .= $trailingSlash;
				}

				$queryString = Router::getQueryString();
				$queryString = $queryString ? '?' . $queryString : '';

				$linkHref = '/' . $language[0] . $routeString . $pathParamsString . $queryString;

				$languageLink = new Link([
					'href'=>$linkHref,
					'anchor'=>$language[0],
					'autoFinalize'=>true
				]);

				$switcherMenu->addItem($languageLink);
			}
		}

		$switcherMenu->finalize();
		$this->lv('switcherMenu', $switcherMenu);
	}
}
