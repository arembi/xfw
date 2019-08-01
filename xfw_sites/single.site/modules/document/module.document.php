<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\SEO;
use Arembi\Xfw\Core\Models\Route;
use Arembi\Xfw\Core\Models\Domain;

class Document extends DocumentBase {

	protected static $hasModel = true;

	public function main(&$options)
	{
		$this->loadPathParams($options);

		$this->lv('lang', App::getLang());
		$this->lv('primaryModule', $options['primaryModule']);

		HEAD::addMeta(['charset' => 'utf-8']);
		HEAD::setTitle('Default Document Title');
	}


}
