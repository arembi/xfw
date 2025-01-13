<?php

/*

The purpose of this module is to take control over linking, to keep
the layouts and contents always up to date

There are three methods to specify a links href attribute:
1. Direct method:
The href behaves like the standard HTML href attribute, you can set it to a
absolute or relative reference. For instance:
$options['href'] = "http://example.com";

2. System link ID method:
Once a link has been saved in the system, you can create a href to it by setting
the href module variable to @ID. For example:
$options['href'] = "@123";

3. Constructing method
Construct hrefs by setting the route ID and the path parameters
*/

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;
use function Arembi\Xfw\Misc\parseHtmlAttributes;

class LinkBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;
	private $hrefRaw;
	private $href;
	/*
	$options = [
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
	]
	*/
	
	
	protected function main(&$options)
	{
		$lang = App::getLang();

		if (empty($options['href'])) {
			Debug::alert('No href attribute given to a link.', 'f');
			return false;
		}

		$this->hrefRaw = $options['href'];
		
		$this->href = Router::url($options['href'], $options);
		
		if (Router::getFullURL() == $this->href) {
			$class = 'origo ';
		} else {
			$class = null;
		}

		if (!empty($options['class'])) {
			if (is_array($options['class'])) {
				$class .= implode(' ', $options['class']);
			} elseif (is_string($options['class'])) {
				$class .= $options['class'];
			}
		}

		$anchor = $options['anchor'][$lang]
			?? $options['anchor']
			?? '';

		$title = $options['title'][$lang]
			?? $options['title']
			?? '';

		if (isset($options['follow']) && $options['follow'] === false) {
			if (empty($options['rel'])) {
				$options['rel'] = 'nofollow';
			} else {
				$options['rel'] .= ' nofollow';
			}
		}

		$attributes = parseHtmlAttributes([
			'href'=>htmlspecialchars($this->href),
			'style'=>$options['style'] ?? null,
			'id'=>$options['id'] ?? null,
			'class'=>$class,
			'title'=>$title,
			'target'=>$options['target'] ?? null,
			'rel'=>$options['rel'] ?? null
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
