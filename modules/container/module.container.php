<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;

class ContainerBase extends ModuleBase {

	protected static $autoloadModel = true;

	protected $id;
	protected $title;
	protected $content;
	protected $displayTitle;


	protected function init()
	{
		$this
			->id($this->params['id'] ?? 0)
			->title($this->params['title'] ?? '')
			->content($this->params['content'] ?? '')
			->displayTitle($this->params['displayTitle'] ?? true);
		
		if ($this->id() != 0) {
			$this->invokeModel();
			$htmlContent = $this->model->getContentById($this->id());
			$this
				->title($htmlContent->title)
				->content($htmlContent->content);
		}
	}


	public function finalize(): void
	{
	 	$this
			->lv('title', $this->title)
			->lv('content', $this->content)
			->lv('displayTitle', $this->displayTitle);
	}


	public function id(int|null $id = null): int|ContainerBase
	{
		if ($id === null) {
			return $this->id;
		}

		$this->id = $id;
		return $this;
	}


	public function title(string|array|null $title = null): string|array|ContainerBase
	{
		if ($title === null) {
			return $this->title;
		}

		$this->title = $title;
		return $this;
	}


	public function content($content = null): string|array|ContainerBase
	{
		if ($content === null) {
			return $this->content;
		}

		$this->content = $content;
		return $this;
	}


	public function displayTitle(?bool $displayTitle = null): bool|ContainerBase
	{
		if ($displayTitle === null) {
			return $this->displayTitle;
		}

		$this->displayTitle = $displayTitle;
		return $this;
	}
}
