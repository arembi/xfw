<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;

class ContainerBase extends ModuleBase {

	protected static $hasModel = false;

	protected $title;
	protected $content;


	protected function init()
	{
		$this->title = $this->params['title'] ?? '';
		$this->content = $this->params['content'] ?? '';
	}


	public function title(string|array|null $title = null)
	{
		if ($title === null) {
			return $this->title;
		}

		$this->title = $title;
		return $this;
	}


	public function content($content = null)
	{
		if ($content === null) {
			return $this->content;
		}

		$this->content = $content;
		return $this;
	}


	public function finalize()
	{
	 	$this->lv('title', $this->title);
	 	$this->lv('content', $this->content);
	 	return $this;
	}


}
