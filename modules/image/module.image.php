<?php

namespace Arembi\Xfw\Module;

use \Arembi\Xfw\Core\Router;
use function \Arembi\Xfw\Misc\parseHtmlAttributes;

class ImageBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;
	/*
	 * params
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

	protected function init()
	{
		$attributes['id'] = $this->params['htmlId'] ?? null;
		$attributes['class'] = $this->params['htmlClass'] ?? null;
		$attributes['alt'] = $this->params['htmlAlt'] ?? null;
		$attributes['title'] = $this->params['htmlTitle'] ?? null;
		$attributes['style'] = $this->params['htmlStyle'] ?? null;
		$attributes['width'] = $this->params['htmlWidth'] ?? null;
		$attributes['height'] = $this->params['htmlHeight'] ?? null;
		$attributes['src'] = Router::url($this->params['src']);

		$this->lv('attributes', parseHtmlAttributes($attributes));
	}
}
