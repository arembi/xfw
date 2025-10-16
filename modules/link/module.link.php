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
use function Arembi\Xfw\Misc\parseHtmlAttributes;

class LinkBase extends ModuleBase {

	protected static $hasModel = false;
	
	private $href;
	private $hrefOverrides;
	private $anchor;
	private $htmlTitle;
	private $htmlId;
	private $htmlClass;
	private $htmlStyle;
	private $htmlTarget;
	private $htmlRel;
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
		'queryParams'=>null,
		'pageNumber' => null
	*/
	
	
	protected function init()
	{
		$this->href = '';
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

		$href = Router::url($this->params['href'], $this->hrefOverrides);
		if ($href === null) {
			$this->error('Could not retrieve href.');
			return;
		}

		$this->href = $href;
		$this->anchor = $this->params['anchor'] ?? '';
		$this->htmlTitle = $this->params['title'] ?? [];
		$this->htmlId = $this->params['htmlId'] ?? null;
		$this->htmlClass = Router::getFullURL() == $this->href ? 'origo ' : '' ;
		$this->htmlStyle = $this->params['style'] ?? null;
		$this->htmlTarget = $this->params['target'] ?? null;
		$this->htmlRel = $this->params['rel'] ?? null;

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


	public function finalize()
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

		$this->lv('attributes', $attributes);
		$this->lv('anchor', $this->anchor);
	}


	public function href(?string $href = null): string|LinkBase
	{
		if ($href === null) {
			return $this->href;
		}
		$this->href = $href;
		return $this;
	}


	public function anchor(?string $anchor = null): string|LinkBase
	{
		if ($anchor === null) {
			return $this->anchor;
		}
		$this->anchor = $anchor;
		return $this;
	}


	public function htmlTitle(?string $htmlTitle = null): string|LinkBase
	{
		if ($htmlTitle === null) {
			return $this->htmlTitle;
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
