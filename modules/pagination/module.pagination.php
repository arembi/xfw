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

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\Language;

class PaginationBase extends ModuleBase {

	protected static $autoloadModel = false;

	protected static $defaults = [
		'itemsPerPage' => 10,
		'listType' => 'all',
		'numberingType' => 'arabic'
	];


	protected function init()
	{
		// If the number of items has not been set, there is no point to place a
		// paginator on the site
		if (empty($this->params['numberOfItems'])) {
			$this->error('Number of items not set.');
		}

		// Setting default values if needed
		$this->params = array_merge(self::$defaults, $this->params);

		/*
		Checking which page are we on
		If the value has not been set, we assume its the first page
		*/
		if (!empty(Router::getPageNumber())) {
			$this->params['currentPage'] = Router::getPageNumber();
		} elseif(!empty($this->params['itemCount']) && !empty($this->params['itemsPerPage'])) {
			$this->params['currentPage'] = 1;
		}

		$id = (!empty($this->params['htmlId']))
			? ' id="' . $this->params['htmlId'] . '"'
			: '';

		$class = (!empty($this->params['htmlClass']))
			? ' class="' . $this->params['htmlClass'] . '"'
			: '';

		$style = (!empty($this->params['htmlStyle']))
			? ' style="' . $this->params['htmlStyle'] . '"'
			: '';

		$etc = $this->params['etc'] ?? '';

		/*
		 * Constructing links
		 * */
		 $links = [];

		 $lastPage = round($this->params['numberOfItems'] / $this->params['itemsPerPage']);
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

			switch ($this->params['numberingType']) {
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

		$this
			->lv('links', $links)
			->lv('id', $id)
			->lv('class', $class)
			->lv('style', $style)
			->lv('etc', $etc);

	}

}
