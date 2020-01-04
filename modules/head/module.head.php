<?php

/*
The <head>
	The head will be sent to the output after the rest of the embedded modules.
	You can set f.i. the title anywhere in the system by calling the
	Head::setTitle('your title') function.
	Same goes to the other head elements: css, JS, meta tags.
*/

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Seo;

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
	private static $js = [];
	private static $css = [];
	private static $custom = [];

	protected $options = [
		'title' => null,
		'base' => null,
		'meta' => null,
		'link' => null,
		'favicon' => null,
		'css' => null,
		'js' => null
		];


	protected function main(&$options)
	{
		$meta = '';
		$link = '';
		$css = '';
		$js = '';
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

		// Get the robots meta tag contents
		self::generateRobotsMeta();

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
		foreach (self::$js as $cjs) {
			// $js[0] is the js code or src, $js[1] is the async attribute
			if (strpos($cjs[0], '<script') !== false) {
				if (strpos($cjs[0], '</script>') == strlen($js[0]) - 9) {
					$js .= $cjs[0] . PHP_EOL;
				} else {
					Debug::alert('Some JavaScript code is missing due to incorrect embedding.');
				}
			} else {
				$js .= '<script';
				$js .= $cjs[1] ? ' async' : '';
				$js .= ' src="' . (strpos($cjs[0], '//') !== false ? '' : Router::$hostURL) . htmlspecialchars($cjs[0]) . '"';
				$js .= '></script>' . PHP_EOL;
			}
		}

		/*
		Adding css HTML entities to the code
		*/

		foreach (self::$css as $css) {
			$css .= '<link rel="stylesheet" href="' . (strpos($css, '//') !== false ? '' : Router::getHostUrl()) . htmlspecialchars($css) . '" type="text/css">' . PHP_EOL;
		}

		/*
		Adding custom elements to the code
		*/

		foreach (self::$custom as $c) {
			$custom .= $c . PHP_EOL;
		}

		$this->lv('title', $title);
		$this->lv('meta', $meta);
		$this->lv('css', $css);
		$this->lv('js', $js);
		$this->lv('custom', $custom);
		$this->lv('link', $link);
		$this->lv('base', $base);
		$this->lv('favicon', $favicon);
	}


	public static function setTitle(string $title)
	{
		self::$title = $title;
	}


	public static function getTitle()
	{
		return self::$title;
	}


	public static function setMetaDescription(string $description)
	{
		self::$metaDescription = $description;
	}


	public static function getMetaDescription()
	{
		return self::$metaDescription;
	}


	public static function setBaseUrl(string $url)
	{
		self::$baseUrl = $url;
	}


	public static function setFaviconUrl(string $url)
	{
		self::$faviconUrl = $url;
	}


	public static function addJS($js, bool $async = false)
	{
		if (is_array($js)) {
			foreach ($js as $cjs) {
				$cjs = trim($cjs);
				if (!in_array($cjs, self::$js)) {
					self::$js[] = [$cjs, $async];
				}
			}
		} else {
			$js = trim($js);
			if (!in_array($js, self::$js)) {
				self::$js[] = [$js, $async];
			}
		}
	}


	public static function addCss($css)
	{
		if (is_array($css)) {
			foreach ($css as $ccss) {
				$ccss = trim($ccss);
				$ccss = str_replace(SITES, Router::getHostUrl(), $ccss);
				if (!in_array($ccss, self::$css)) {
					self::$css[] = $ccss;
				}
			}
		} elseif (is_string($css)) {
			$css = trim($css);
			$css = str_replace(SITES, Router::getHostUrl(), $css);
			if (!in_array($css, self::$css)) {
				self::$css[] = $css;
			}
		} else {
			Debug::alert('Not supported css: ' . print_r($css, true));
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



	public static function canonical(string $href)
	{
		self::$canonicalUrl = $href;
	}



	public static function addCustomHTML($custom)
	{
		self::$custom[] = $custom;
	}


	public static function generateRobotsMeta()
	{
		$meta = [];
		$meta['index'] = Seo::isIndexable() ? 'index' : 'noindex';
		$meta['follow'] = Seo::isFollowable() ? 'follow' : 'nofollow';
		$meta['noArchive'] = Seo::isArchivable() ? false : 'noarchive';
		$meta['maxSnippet'] = 'max-snippet:' . Seo::getMaxSnippet();
		$meta['maxImagePreview'] = 'max-image-preview:' . Seo::getMaxImagePreview();
		$meta['max-video-preview'] = 'max-video-preview:' . Seo::getMaxVideoPreview();
		$meta['noTranslate'] = Seo::isTranslatable() ? false : 'notranslate';
		$meta['noImageIndex'] = Seo::areImagesIndexable() ? false : 'noimageindex';

		$meta = array_filter($meta);

		$content = implode(', ', $meta);

		self::addMeta(['name' => 'robots', 'content' => $content]);
	}

}
