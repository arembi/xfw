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

	private $href;

	private $hrefRaw;

	protected $options = [
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
		];

	protected function main(&$options)
	{
		$lang = App::getLang();

		if (empty($options['href'])) {
			Debug::alert('No href attribute given to a link.', 'f');
			return false;
		}

		$this->hrefRaw = $options['href'];
		
		
		if (substr($options['href'], 0 ,2) === '//') {
			$options['href'] = Router::getProtocol() . substr($options['href'], 2);
		}

		// First character in the href determines what to do
		$href1 = $options['href'][0];
		
		// Constructing the href
		if (in_array($href1, ['@', '+', '/'])) {

			$hrefParts = explode('?', $options['href'], 2);

			// Converting the queryString to an array
			$queryStringParts = [];

			// Getting data already present in the query string
			if (isset($hrefParts[1])) {
				parse_str($hrefParts[1], $queryStringParts);
			}

			// Assembling the href part
			if ($href1 == '@') {
				//System link mode

				//The link will be generated based on the information stored in the
				//database.

				//Required values
				//	ID: the id of the link record in the database
				
				$linkID = substr($hrefParts[0], 1);
				$href = Router::href('link', $linkID);
			} elseif ($href1 == '+') {
				
				//Route mode

				//Required values
				//	route: the route ID
				//Optional values
				//	lang: language marker (en, hu etc.), the current language will be used if not given
				//		(if a route is not available, a 404 error will be thrown)
				//Usage example:
				//	href = "+route=19+lang=hu+pathParam1=abc+pathParam2=xyz"
				//	anchor = "sometext"
				//
				$data = [];

				$params = explode('+', substr($hrefParts[0], 1));

				foreach ($params as $p) {
					$cp = explode('=', $p, 2);

					$data[trim($cp[0])] = trim($cp[1]);
				}

				$href = Router::href('route', $data);

			} else {
				// Starts with a /
				$href = [
					'lang' => $lang,
					'base' => Router::gethostURL() . $hrefParts[0],
					'queryStringParts' => $queryStringParts
					];
			}
			
			if (is_array($href) && $href['base']) {
				// Adding the page number to the query string
				if (!empty($options['pageNumber'])) {
					$queryStringParts[Router::getPaginationParams()[$href['lang']]] = $options['pageNumber'];
				}

				$queryStringParts = array_merge($queryStringParts, $href['queryStringParts']);

				if (isset($options['queryParams']) && is_array($options['queryParams'])) {
					$queryStringParts = array_merge($queryStringParts, $options['queryParams']);
				}

				// Elements in the query string can be removed with the remove moduleVar
				if (isset($options['remove']) && is_array($options['remove'])) {
					$queryStringParts = array_diff_key($queryStringParts, array_flip($options['remove']));
				}

				// The directly given parameters will override the saved ones
				$queryString = http_build_query($queryStringParts);

				// Adding the questionmark if it was not present
				if ($queryString && strpos($href['base'], '?') === false) {
					$queryString = '?' . $queryString;
				}
				$options['href'] = $href['base'] . $queryString;
			} else {
				Debug::alert('href ' . $options['href'] . ' does not exist in the system.', 'w');
				$this->layoutHTML = false;
			}
		}

		$this->href = $options['href'];

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
