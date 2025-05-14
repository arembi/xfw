<?php

namespace Arembi\Xfw\Module;

class BoxBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;

	protected $params = [
		'title' => 'default title',
		'content' => 'default content',
		'kedvenc_allat' => ['kutya']
	];

	protected function main()
	{
		$this->lv('title', $this->params['title']);
		$this->lv('content', $this->params['content']);
		$this->lv('kedvenc_allat', $this->params['kedvenc_allat']);
	}

}
