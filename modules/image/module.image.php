<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;

class ImageBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;
	/*
	 * options
	 * src
	 * 	can be a href or an internal identifier
	 * alt
	 * title
	 * height
	 * width
	 * id
	 * class
	 * style
	 * */

	protected function main(&$options)
	{
		$attributes['id'] = $options['id'] ?? null;
		$attributes['class'] = $options['class'] ?? null;
		$attributes['alt'] = $options['alt'] ?? null;
		$attributes['title'] = $options['title'] ?? null;
		$attributes['style'] = $options['style'] ?? null;
		$attributes['width'] = $options['width'] ?? null;
		$attributes['height'] = $options['height'] ?? null;

		$link = new Link(['href'=> $options['src']]);

		$attributes['src'] = $link->getHref();

		$a = \Arembi\Xfw\Misc\parseHtmlAttributes($attributes);

		$this->lv('attributes', $a);
	}
}
