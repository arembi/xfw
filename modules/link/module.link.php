<?php

/*

The purpose of this module is to take control over linking, to keep
the layouts and contents always up to date

There are three methods to specify a links href attribute:
1. Direct method:
The href behaves like the standard HTML href attribute, you can set it to a
absolute or relative reference. For instance:
$this->params['href'] = "http://example.com";

2. System link ID method:
Once a link has been saved in the system, you can create a href to it by setting
the href module variable to @ID. For example:
$this->params['href'] = "@123";

3. Constructing method
Construct hrefs by setting the route ID and the path parameters
*/

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Settings;

use function Arembi\Xfw\Misc\parseHtmlAttributes;

class LinkBase extends ModuleBase {

	protected static $autoloadModel = false;
	
	protected $href;
	protected $hrefLang;
	protected $hrefOverrides;
	protected $anchor;
	protected $htmlTitle;
	protected $htmlId;
	protected $htmlClass;
	protected $htmlStyle;
	protected $htmlTarget;
	protected $htmlRel;
	
	/*
	params:
		'href' => null,
		'remove' => null, // remove parameters from the query string
		'style' => null, // HTML attribute
		'id' => null, // HTML attribute
		'class' => null, // HTML attribute
		'title' => null, // HTML attribute
		'target' => null, // HTML attribute
		'follow' => true, // HTML rel nofollow attribute
		'rel' => null, // HTML attribute
		'anchor' => null, // the anchor text
		'queryParameters'=>null,
		'pageNumber' => null
	*/
	
	
	protected function init()
	{
		$this->href = '';
		$this->hrefLang = '';
		$this->hrefOverrides = $this->params['hrefOverrides'] ?? [];
		$this->anchor = '';
		$this->htmlTitle = '';
		$this->htmlId = '';
		$this->htmlClass = '';
		$this->htmlStyle = '';
		$this->htmlTarget = '';
		$this->htmlRel = '';
	
		if (empty($this->params['href'])) {
			$this->error('href has not been set.');
			return;
		}

		// If instantiated with an id present, it will override the href parameter
		if (isset($this->params['id']) && $this->params['id'] != 0) {
			$this->params['href'] = '@' . $this->params['id'];
		}
		
		$this
			->href($this->params['href'], $this->hrefOverrides)
			->anchor($this->params['anchor'] ?? '')
			->htmlTitle($this->params['title'] ?? '')
			->htmlId($this->params['htmlId'] ?? '')
			->htmlClass(Router::getFullURL() == $this->href ? 'origo ' : '')
			->htmlStyle($this->params['style'] ?? '')
			->htmlTarget($this->params['target'] ?? '')
			->htmlRel($this->params['rel'] ?? '');

		if (!empty($this->params['class'])) {
			if (is_array($this->params['class'])) {
				$this->htmlClass .= implode(' ', $this->params['class']);
			} elseif (is_string($this->params['class'])) {
				$this->htmlClass .= $this->params['class'];
			}
		}

		if (isset($this->params['follow']) && $this->params['follow'] === false) {
			if (empty($this->params['rel'])) {
				$this->htmlRel = 'nofollow';
			} else {
				$this->htmlRel .= ' nofollow';
			}
		}
	}


	public function finalize(): void
	{
		$attributes = parseHtmlAttributes([
			'href'=>$this->href,
			'title'=>$this->htmlTitle,
			'id'=>$this->htmlId,
			'class'=>$this->htmlClass,
			'style'=>$this->htmlStyle,
			'target'=>$this->htmlTarget,
			'rel'=>$this->htmlRel
		]);

		$this
			->lv('attributes', $attributes)
			->lv('anchor', $this->anchor);
	}


	public function href(?string $href = null, array $hrefOverrides = []): string|LinkBase
	{
		if ($href === null) {
			return $this->href;
		}
		$url = Router::url($href, $hrefOverrides, true);
		if ($url) {
			$this->href = $url['url'];
			$this->hrefLang = $url['lang'];	
		} else {
			$this->error('Cannot assign href to link: href not valid.');
		}
		return $this;
	}


	public function hrefLang(): string
	{
		return $this->hrefLang;
	}


	public function lang(): string
	{
		return $this->hrefLang();
	}


	public function anchor(string|array|null $anchor = null): string|array|LinkBase
	{
		if ($anchor === null) {
			return $this->anchor;
		}
		if (is_array($anchor)) {
			$anchor = array_filter($anchor, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}
		$this->anchor = $anchor;
		return $this;
	}


	public function htmlTitle(string|array|null $htmlTitle = null): string|array|LinkBase
	{
		if ($htmlTitle === null) {
			return $this->htmlTitle;
		}
		if (is_array($htmlTitle)) {
			$htmlTitle = array_filter($htmlTitle, fn ($key) => !in_array($key, Settings::get('availableLanguages')), ARRAY_FILTER_USE_KEY);
		}
		$this->htmlTitle = $htmlTitle;
		return $this;
	}


	public function htmlId(?string $htmlId = null): string|LinkBase
	{
		if ($htmlId === null) {
			return $this->htmlId;
		}
		$this->htmlId = $htmlId;
		return $this;
	}


	public function htmlClass(?string $htmlClass = null): string|LinkBase
	{
		if ($htmlClass === null) {
			return $this->htmlClass;
		}
		$this->htmlClass = $htmlClass;
		return $this;
	}


	public function htmlStyle(?string $htmlStyle = null): string|LinkBase
	{
		if ($htmlStyle === null) {
			return $this->htmlStyle;
		}
		$this->htmlStyle = $htmlStyle;
		return $this;
	}

	
	public function htmlTarget(?string $htmlTarget = null): string|LinkBase
	{
		if ($htmlTarget === null) {
			return $this->htmlTarget;
		}
		$this->htmlTarget = $htmlTarget;
		return $this;
	}


	public function htmlRel(?string $htmlRel = null): string|LinkBase
	{
		if ($htmlRel === null) {
			return $this->htmlRel;
		}
		$this->htmlRel = $htmlRel;
		return $this;
	}


	protected function follow(bool $follow = true): LinkBase
	{
		$this->params['follow'] = $follow;
		return $this;
	}

}
