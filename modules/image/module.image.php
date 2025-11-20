<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;
use function Arembi\Xfw\Misc\parseHtmlAttributes;

class ImageBase extends ModuleBase {

	protected static $autoloadModel = false;
	
	protected $htmlId;
	protected $htmlClass;
	protected $htmlAlt;
	protected $htmlTitle;
	protected $htmlStyle;
	protected $htmlWidth;
	protected $htmlHeight;
	protected $htmlSrc;


	protected function init(): void
	{
		$this
			->htmlId($this->params['htmlId'] ?? '')
			->htmlClass($this->params['htmlClass'] ?? '')
			->htmlAlt($this->params['htmlAlt'] ?? '')
			->htmlTitle($this->params['htmlTitle'] ?? '')
			->htmlStyle($this->params['htmlStyle'] ?? '')
			->htmlWidth($this->params['htmlWidth'] ?? '')
			->htmlHeight($this->params['htmlHeight'] ?? '')
			->src($this->params['src']);
	}

	
	protected function finalize(): void
	{
		$attributes = [
			'id'=>$this->htmlId,
			'class'=>$this->htmlClass,
			'alt'=>$this->htmlAlt,
			'title'=>$this->htmlTitle,
			'style'=>$this->htmlStyle,
			'width'=>$this->htmlWidth,
			'height'=>$this->htmlHeight,
			'src'=>Router::url($this->htmlSrc)
		];

		$this->lv('attributes', parseHtmlAttributes($attributes));
	}


	public function htmlId(?string $id = null): string|ImageBase
	{
		if ($id === null) {
			return $this->htmlId;
		}
		$this->htmlId = $id;
		return $this;
	}


	public function htmlClass(?string $class = null): string|ImageBase
	{
		if ($class === null) {
			return $this->htmlClass;
		}
		$this->htmlClass = $class;
		return $this;
	}


	public function htmlAlt(string|array|null $alt = null): string|array|ImageBase
	{
		if ($alt === null) {
			return $this->htmlAlt;
		}
		
		if (is_array($alt)) {
			$alt = array_filter($alt, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}

		$this->htmlAlt = $alt;
		return $this;
	}


	public function htmlTitle(string|array|null $title = null): string|array|ImageBase
	{
		if ($title === null) {
			return $this->htmlTitle;
		}
		
		if (is_array($title)) {
			$title = array_filter($title, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}

		$this->htmlTitle = $title;
		return $this;
	}


	public function htmlStyle(?string $style = null): string|ImageBase
	{
		if ($style === null) {
			return $this->htmlStyle;
		}
		$this->htmlStyle = $style;
		return $this;
	}


	public function htmlWidth(?string $width = null): string|ImageBase
	{
		if ($width === null) {
			return $this->htmlWidth;
		}
		$this->htmlWidth = $width;
		return $this;
	}


	public function htmlHeight(?string $height = null): string|ImageBase
	{
		if ($height === null) {
			return $this->htmlHeight;
		}
		$this->htmlHeight = $height;
		return $this;
	}


	public function src(?string $src = null): string|ImageBase
	{
		if ($src === null) {
			return $this->htmlSrc;
		}
		$src = Router::url($this->params['src']);
		if ($src) {
			$this->htmlSrc = $src;
		} else {
			$this->error('Invalid image src given.');	
		}
		return $this;
	}
}
