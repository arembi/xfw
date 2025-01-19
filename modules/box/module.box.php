<?php

namespace Arembi\Xfw\Module;

class BoxBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;

	protected $options = [
		'title' => 'default title',
		'content' => 'default content',
		'kedvenc_allat' => ['kutya']
	];

	protected function main(&$options)
	{
		$this->lv('title', $options['title']);
		$this->lv('content', $options['content']);
		$this->lv('kedvenc_allat', $options['kedvenc_allat']);
	}

}
