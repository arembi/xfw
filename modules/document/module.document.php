<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Models\Route;
use Arembi\Xfw\Core\Models\Domain;

class DocumentBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = true;


	public function main(&$options)
	{
		$this->loadPathParams($options);

		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $options['primaryModule']);

		Head::addMeta(['charset' => 'utf-8']);
		Head::setTitle('Default Document Title');

	}
}
