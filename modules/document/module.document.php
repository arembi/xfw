<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Inc\Seo;


class DocumentBase extends ModuleBase {

	protected static $hasModel = false;


	public function init()
	{
		$this->loadPathParams();

		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $this->params['primaryModule']);
		$this->lv('primaryModuleParams', $this->params['primaryModuleParams']);

		Head::addMeta(['charset' => 'utf-8']);
		Seo::title('&#x1D6BE; Framework by Arembi');
		Seo::metaDescription('&#x1D6BE;fw - The &#x1D6BE; PHP FrameWork created by Arembi');
	}
}
