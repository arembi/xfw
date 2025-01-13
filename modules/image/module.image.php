<?php

namespace Arembi\Xfw\Module;

use \Arembi\Xfw\Core\Router;
use function \Arembi\Xfw\Misc\parseHtmlAttributes;

class ImageBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;
	/*
	$options = [
		src,
		id,
		class,
		alt,
		title,
		style,
		width,
		height
	]
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

		$attributes['src'] = Router::url($options['src']);

		$this->lv('attributes', parseHtmlAttributes($attributes));
	}
}
