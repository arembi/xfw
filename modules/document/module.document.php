<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Inc\Seo;

class DocumentBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;


	public function main(&$options)
	{
		$this->loadPathParams($options);

		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $options['primaryModule']);
		$this->lv('primaryModuleOptions', $options['primaryModuleOptions']);

		Head::addMeta(['charset' => 'utf-8']);
		Seo::init();
		Seo::title('&#x1D6BE; by Arembi');
		Seo::metaDescription('Xfw - The &#x1D6BE; FrameWork created by Arembi');
	}
}
