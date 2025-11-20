<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class Language_SwitcherBase extends ModuleBase {
  
	protected static $autoloadModel = false;

	protected static $flags = [
		'en'=>'🇬🇧',
		'hu'=>'🇭🇺'
	];

	protected $switcherMenu;
	protected $menuLayout;
	protected $menuLayoutVariant;


	protected function init()
	{
		if (!Settings::get('multiLang')) {
			$this->error('Site is not nultilingual, cannot load the Language Switcher.');
		}

		$this
			->menuLayout($this->params['menuLayout'] ?? Settings::get('defaultModuleLayout'))
			->menuLayoutVariant($this->params['menuLayoutVariant'] ?? Settings::get('defaultModuleLayoutVariant'));

		$availableLanguages = Settings::get('availableLanguages');
		
		$this->switcherMenu(new Menu([
			'layout'=>$this->menuLayout,
			'layoutVariant'=>$this->menuLayoutVariant,
			'title'=>[
				'en'=>'Languages',
				'hu'=>'Nyelvek'
			],
			'displayTitle'=>false
		]));
		
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
					'anchor'=>self::$flags[$language[0]] ?? $language[0],
					'autoFinalize'=>true
				]);

				$this->switcherMenu->addItem($languageLink);
			}
		}
		$this->switcherMenu->finalize();
	}


	public function finalize(): void
	{
		$this->lv('switcherMenu', $this->switcherMenu);
	}


	public function switcherMenu(?Menu $menu = null): Menu|Language_SwitcherBase
	{
		if ($menu === null) {
			return $this->switcherMenu;
		}

		$this->switcherMenu = $menu;
		return $this;
	}


	public function menuLayout(?string $layout): string|Language_SwitcherBase
	{
		if ($layout === null) {
			return $this->menuLayout;
		}

		$this->menuLayout = $layout;
		return $this;
	}


	public function menuLayoutVariant(?string $layoutVariant): string|Language_SwitcherBase
	{
		if ($layoutVariant === null) {
			return $this->menuLayoutVariant;
		}

		$this->menuLayoutVariant = $layoutVariant;
		return $this;
	}
}
