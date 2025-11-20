<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;

class Body_StartBase extends ModuleBase {
	protected static $autoloadModel = false;

	private static $meta = [];
	private static $JS = [];
	private static $CSS = [];
	private static $custom = [];


	protected function init()
	{
		$JS = '';
		$custom = '';

		// Adding JavaScript HTML entities to the code
		foreach (self::$JS as $js) {
			// $js[0] is the js code or src, $js[1] is the async attribute
			if (strpos($js[0], '<script') !== false) {
				if (strpos($js[0], '</script>') == strlen($js[0]) - 9) {
					$JS .= $js[0] . PHP_EOL;
				} else {
					Debug::alert('Some JavaScript code is missing due to incorrect embedding.', 'f');
				}
			} else {
				$JS .= '<script';
				$JS .= $js[1] ? ' async' : '';
				$JS .= ' src="' . (strpos($js[0], '//') !== false ? '' : Router::getHostUrl()) . htmlspecialchars($js[0]) . '"';
				$JS .= '></script>' . PHP_EOL;
			}
		}

		/*
		Adding custom elements to the code
		*/

		foreach (self::$custom as $c) {
			$custom .= $c . PHP_EOL;
		}

		$this
			->lv('JS', $JS)
			->lv('custom', $custom);
	}


	public static function addJS($JS, $async = false)
	{
		$async = ($async !== false); // convert everything to boolean

		if (is_array($JS)) {
			foreach ($JS as $cJS) {
				$cJS = trim($cJS);
				if (!in_array($cJS, self::$JS)) {
					self::$JS[] = [$cJS, $async];
				}
			}
		} else {
			$JS = trim($JS);
			if (!in_array($JS, self::$JS)) {
				self::$JS[] = [$JS, $async];
			}
		}
	}


	public static function addCustomHtml($custom)
	{
		self::$custom[] = $custom;
	}

}
