<?php

/*
The <head>
	The head will be sent to the output after the rest of the embedded modules.
	You can set f.i. the title anywhere in the system by calling the
	Head::setTitle('your title') function.
	Same goes to the other head elements: CSS, JS, meta tags.
*/

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Router;

class HeadBase extends \Arembi\Xfw\Core\ModuleCore {
	protected static $hasModel = false;

	private static $title = '';
	private static $metaDescription = '';
	private static $metaKeywords = '';

	private static $meta = [];
	private static $link = [];
	private static $baseUrl = null;
	private static $faviconUrl = null;
	private static $canonicalUrl = null;
	private static $JS = [];
	private static $CSS = [];
	private static $custom = [];

	public static $setBy = [];


	protected $options = [
		'title' => null,
		'base' => null,
		'meta' => null,
		'link' => null,
		'favicon' => null,
		'CSS' => null,
		'JS' => null
		];


	protected function main(&$options)
	{
		$meta = '';
		$link = '';
		$CSS = '';
		$JS = '';
		$custom = '';

		// Creating title tag
		$title = '<title>' . self::$title . '</title>';
		if(!empty(self::$setBy['title'])){
			Debug::alert('The title was set by ' . self::$setBy['title'] . '.', 'n');
		}

		// Creating meta description tag
		self::addMeta(['name' => 'description', 'content' => self::$metaDescription]);
		if (!empty(self::$setBy['metaDescription'])) {
			Debug::alert('The title was set by ' . self::$setBy['metaDescription'] . '.', 'n');
		}

		// Creating meta keywords tag
		self::addMeta(['name' => 'keywords', 'content' => self::$metaKeywords]);
		if (!empty(self::$setBy['metaKeywords'])) {
			Debug::alert('The title was set by ' . self::$setBy['metaKeywords'] . '.', 'n');
		}

		// Creating the <base>
		$base = self::$baseUrl !== null
			? '<base href="' . self::$baseUrl . '">'
			: '';

		// Creating the favicon <link>
		if (self::$faviconUrl !== null) {
			$iconType = \Arembi\Xfw\Misc\getFileExtension(self::$faviconUrl);
			$favicon = '<link rel="icon" type="image/' . $iconType . '" href="' . self::$faviconUrl . '">';
		} else {
			$favicon = '';
		}

		// Adding meta HTML entities to the code
		if (!empty(self::$meta['charset'])) {
			$meta .= '<meta charset="' . self::$meta['charset'] . '">' . PHP_EOL;
		}

		if (!empty(self::$meta['name'])) {
			foreach (self::$meta['name'] as $name => $content) {
				$metatag= '<meta name="' . $name . '"' . ' content="' . $content . '">' . PHP_EOL;
				$meta .= $metatag;
			}
		}

		if (!empty(self::$meta['http-equiv'])) {
			foreach (self::$meta['http-equiv'] as $name => $content) {
				$metatag= '<meta http-equiv="' . $name . '"' . ' content="' . $content . '">' . PHP_EOL;
				$meta .= $metatag;
			}
		}

		if (empty($meta)) {
			$meta = '';
		}

		// Adding link HTML entities to the code
		foreach (self::$link as $linkData) {
			$linkTag = '<link';

			foreach ($linkData as $attribute => $value) {
				$linkTag .= ' ' . htmlspecialchars($attribute) . '="' . htmlspecialchars($value) . '"';
			}

			$linkTag .= '>' . PHP_EOL;

			$link .= $linkTag;
		}

		// Adding the canonical link
		if (!empty(self::$canonicalUrl)) {
			$l = new Link(['href'=>self::$canonicalUrl]);
			$link .= '<link rel="canonical" href="' . $l->getHref() . '">' . PHP_EOL;
		}

		// Adding JavaScript HTML entities to the code
		foreach (self::$JS as $js) {
			// $js[0] is the js code or src, $js[1] is the async attribute
			if (strpos($js[0], '<script') !== false) {
				if (strpos($js[0], '</script>') == strlen($js[0]) - 9) {
					$JS .= $js[0] . PHP_EOL;
				} else {
					Debug::alert('Some JavaScript code is missing due to incorrect embedding.');
				}
			} else {
				$JS .= '<script';
				$JS .= $js[1] ? ' async' : '';
				$JS .= ' src="' . (strpos($js[0], '//') !== false ? '' : Router::$hostURL) . htmlspecialchars($js[0]) . '"';
				$JS .= '></script>' . PHP_EOL;
			}
		}

		/*
		Adding CSS HTML entities to the code
		*/

		foreach (self::$CSS as $css) {
			$CSS .= '<link rel="stylesheet" href="' . (strpos($css, '//') !== false ? '' : Router::getHostUrl()) . htmlspecialchars($css) . '" type="text/css">' . PHP_EOL;
		}

		/*
		Adding custom elements to the code
		*/

		foreach (self::$custom as $c) {
			$custom .= $c . PHP_EOL;
		}

		$this->lv('title', $title);
		$this->lv('meta', $meta);
		$this->lv('CSS', $CSS);
		$this->lv('JS', $JS);
		$this->lv('custom', $custom);
		$this->lv('link', $link);
		$this->lv('base', $base);
		$this->lv('favicon', $favicon);
	}


	public static function setTitle($title, $setBy = false)
	{
		self::$title = $title;
		self::$setBy['title'] = $setBy;
	}


	public static function getTitle()
	{
		return self::$title;
	}


	public static function setMetaDescription($description, $setBy = false)
	{
		self::$metaDescription = $description;
		self::$setBy['metaDescription'] = $setBy;
	}


	public static function getMetaDescription()
	{
		return self::$metaDescription;
	}


	public static function setMetaKeywords($keywords, $setBy = false)
	{
		self::$metaKeywords = $keywords;
		self::$setBy['metaKeywords'] = $setBy;
	}


	public static function getMetaKeywords()
	{
		return self::$metaKeywords;
	}


	public static function setBaseUrl($url = false)
	{
		self::$baseUrl = $url;
	}


	public static function setFaviconUrl($url = false)
	{
		if ($url) self::$faviconUrl = $url;
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


	public static function addCSS($CSS)
	{
		if (is_array($CSS)) {
			foreach ($CSS as $cCSS) {
				$cCSS = trim($cCSS);
				$cCSS = str_replace(SITES, Router::getHostUrl(), $cCSS);
				if (!in_array($cCSS, self::$CSS)) {
					self::$CSS[] = $cCSS;
				}
			}
		} elseif (is_string($CSS)) {
			$CSS = trim($CSS);
			$CSS = str_replace(SITES, Router::getHostUrl(), $CSS);
			if (!in_array($CSS, self::$CSS)) {
				self::$CSS[] = $CSS;
			}
		} else {
			Debug::alert('Not supported CSS: ' . print_r($CSS, true));
		}
	}



	/* Metas should be arrays like this
	 * [
	 * 'attr1' => 'value1',
	 * 'attr2' => 'value2'
	 * ]
	 *
	 *
	 * */

	public static function addMeta(array $meta)
	{
		if (!isset($meta['content'])) {
			$meta['content'] = '';
		}

		if (isset($meta['charset'])) {
			self::$meta['charset'] = $meta['charset'];
		} elseif (isset($meta['name'])) {
			self::$meta['name'][$meta['name']] = $meta['content'];
		} elseif (isset($meta['http-equiv'])) {
			self::$meta['http-equiv'][$meta['http-equiv']] = $meta['content'];
		}
	}



	public static function addLink($link)
	{
		if (is_array($link)) {
			if (!empty($link[0]) && is_array($link[0])) {
				foreach ($link as $l) {
					if (!in_array($l, self::$link)) {
						self::$link[] = $l;
					}
				}
			} else {
				if (!in_array($link, self::$link)) {
					self::$link[] = $link;
				}
			}
		}
	}



	public static function canonical($href, $setBy = false)
	{
		self::$canonicalUrl = $href;
		if ($setBy !== false) {
			self::$setBy['canonical'] = $setBy;
		}
	}



	public static function addCustomHTML($custom)
	{
		self::$custom[] = $custom;
	}

}
