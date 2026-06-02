<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

class DocumentBase extends ModuleBase {

	protected static bool $autoloadModel = false;

	protected ?string $documentAction;
	protected string $primaryModule;
	protected array $primaryModuleParameters;


	public function init()
	{
		$this->documentAction = Router::getRequestedDocumentAction();
		$this->primaryModule = $this->params['primaryModule'];
		$this->primaryModuleParameters = $this->params['primaryModuleParameters'];

		if ($this->documentAction) {
			$this->executeAction($this->documentAction);
		}
	}


	public function finalize()
	{
		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $this->primaryModule);
		$this->lv('primaryModuleParameters', $this->primaryModuleParameters);

		Head::addMeta(['charset' => 'utf-8']);
		
		Head::title(Settings::get('siteName'));
		Head::metaDescription('Created with XfW.');
	}
}
