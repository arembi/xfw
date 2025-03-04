<?php

/*
 * Input
 * 	current page
 * 	total item count
 * 	items per page
 *
 * Output
 * 	links to the other pages
 *
 * Options
 * 	list type
 * 		- all: F 1 2 3 4 5 6 7 8 9 L
 * 		- current5: F ... 18 19 20 21 22 ...  L
 * 	numbering type
 * 		- arabic: 1 2 3 ...
 *    - roman: I II III ...
 * 		- lcAlphabet: a b c ...
 * 		- ucAlphabet: A B C ...
 *
 * Flow
 * 	-	pagination parameter detected in URL, for instance /myblog/sometag?page=2
 *  - the router matches the route with the blog module
 *  - the router stores the pagination parameter
 * */

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Language;

class PaginationBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = false;

	protected static $defaults = [
		'itemsPerPage' => 10,
		'listType' => 'all',
		'numberingType' => 'arabic'
	];


	protected function main(&$options)
	{
		// If the number of items has not been set, there is no point to place a
		// paginator on the site
		if (empty($options['numberOfItems'])) {
			return false;
		}

		// Setting default values if needed
		$options = array_merge(self::$defaults, $options);

		/*
		Checking which page are we on
		If the value has not been set, we assume its the first page
		*/
		if (!empty(Router::$pageNumber)) {
			$options['currentPage'] = Router::$pageNumber;
		} elseif(!empty($options['itemCount']) && !empty($options['itemsPerPage'])) {
			$options['currentPage'] = 1;
		}

		$id = (!empty($options['id']))
			? ' id="' . $options['id'] . '"'
			: '';

		$class = (!empty($options['class']))
			? ' class="' . $options['class'] . '"'
			: '';

		$style = (!empty($options['style']))
			? ' style="' . $options['style'] . '"'
			: '';

		$etc = $options['etc'] ?? '';

		/*
		 * Constructing links
		 * */
		 $links = [];

		 $lastPage = round($options['numberOfItems'] / $options['itemsPerPage']);
		 $routeId = Router::getMatchedRouteId();

		 $linkData = [];
		 $linkData['href'] = "+route=$routeId";

		 $ppo = Router::getMatchedRoutePpo();
		 $pathParams = Router::getPathParams();

		 foreach ($ppo as $key => $param) {
			 $linkData['href'] .= "+$param=$pathParams[$key]";
		 }

		 $linkData['queryParams'] = Router::getQueryString();
		 unset($linkData['queryParams'][Router::getPaginationParam()]);

		 for ($i = 1; $i <= $lastPage; $i++) {
			$linkData['pageNumber'] = $i;

			switch ($options['numberingType']) {
				case 'arabic':
					$linkData['anchor'] = $i;
					break;
				case 'roman':
					$linkData['anchor'] = Language::intToRoman($i);
					break;
				case 'lcAlphabet':
					$alphabet = Language::getAlphabet('en');
					$linkData['anchor'] = $alphabet[$i - 1];
					break;
				case 'ucAlphabet':
					$alphabet = Language::getAlphabet('en');
					$linkData['anchor'] = strtoupper($alphabet[$i - 1]);
					break;
				default:
					break;
			}

			$links[] = $linkData;
		 }

		 $this->lv('links', $links);
		 $this->lv('id', $id);
		 $this->lv('class', $class);
		 $this->lv('style', $style);
		 $this->lv('etc', $etc);

	}

}
