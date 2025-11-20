<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Inc\Seo;

class DocumentBase extends ModuleBase {

	protected static $autoloadModel = false;

	protected $documentAction;
	protected $primaryModule;
	protected $primaryModuleParams;


	public function init()
	{
		$this->documentAction = Router::getRequestedDocumentAction();
		$this->primaryModule = $this->params['primaryModule'];
		$this->primaryModuleParams = $this->params['primaryModuleParams'];

		if ($this->documentAction) {
			$this->executeAction($this->documentAction);
		}
	}


	public function finalize()
	{
		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $this->primaryModule);
		$this->lv('primaryModuleParams', $this->primaryModuleParams);

		Head::addMeta(['charset' => 'utf-8']);
		
		Seo::title('&#x1D6BE; Framework by Arembi');
		Seo::metaDescription('&#x1D6BE;fw - The &#x1D6BE; PHP FrameWork created by Arembi');
	}
}
