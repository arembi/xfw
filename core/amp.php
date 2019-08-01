<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Module\HEAD;
use Arembi\Xfw\Module\Link;

abstract class AMP {

	private static $AMPJS = 'https://cdn.ampproject.org/v0.js';
	private static $styleBoilerPlate = '<style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>';

	public static function init()
	{
		if (App::AMP() == 'on') {
			// Setting the charset to utf-8
			HEAD::addMeta(['charset' => 'utf-8']);
			HEAD::addMeta(['name' => 'viewport', 'content' => 'width=device-width,minimum-scale=1,initial-scale=1']);
			HEAD::addCustom(self::$styleBoilerPlate);

			// Adding the async AMP script
			HEAD::addJS(self::$AMPJS, true);

			// Creating a canonical link tag to the non-AMP version of the page
			$canonical = new Link(['href' => Router::getPath(), 'remove' => ['amp']]);
			HEAD::canonical($canonical->getHref());
		}
	}


}
