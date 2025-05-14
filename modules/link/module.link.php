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

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use function Arembi\Xfw\Misc\parseHtmlAttributes;

class LinkBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;
	private $hrefRaw;
	private $href;
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
	
	
	protected function main()
	{
		// If instantiated with an id present, it will override the href parameter
		if (isset($this->params['id']) && $this->params['id'] !== 0) {
			$this->params['href'] = '@' . $this->params['id'];
		}

		if (empty($this->params['href'])) {
			$this->error('href has not been set.');
			return;
		}

		$href = Router::url($this->params['href'], $this->params);
		if ($href === null) {
			$this->error('Could not retrieve href.');
			return;
		}

		$lang = App::getLang();
		$this->hrefRaw = $this->params['href'];
		$this->href = $href;
		
		if (Router::getFullURL() == $this->href) {
			$class = 'origo ';
		} else {
			$class = null;
		}

		if (!empty($this->params['class'])) {
			if (is_array($this->params['class'])) {
				$class .= implode(' ', $this->params['class']);
			} elseif (is_string($this->params['class'])) {
				$class .= $this->params['class'];
			}
		}

		$anchor = $this->params['anchor'][$lang]
			?? $this->params['anchor']
			?? '';

		$title = $this->params['title'][$lang]
			?? $this->params['title']
			?? '';

		if (isset($this->params['follow']) && $this->params['follow'] === false) {
			if (empty($this->params['rel'])) {
				$this->params['rel'] = 'nofollow';
			} else {
				$this->params['rel'] .= ' nofollow';
			}
		}

		$attributes = parseHtmlAttributes([
			'href'=>htmlspecialchars($this->href),
			'style'=>$this->params['style'] ?? null,
			'id'=>$this->params['htmlId'] ?? null,
			'class'=>$class,
			'title'=>$title,
			'target'=>$this->params['target'] ?? null,
			'rel'=>$this->params['rel'] ?? null
		]);

		$this->lv('attributes', $attributes);
		$this->lv('anchor', $anchor);
	}


	public function getHrefRaw()
	{
		return $this->hrefRaw;
	}


	public function getHref()
	{
		return $this->href;
	}


	// Removes the nofollow rel attribute
	protected function follow($follow = true)
	{
		$this->options['follow'] = $follow;
		return $this;
	}

}
