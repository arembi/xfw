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
use Arembi\Xfw\Inc\Seo;

class HeadBase extends \Arembi\Xfw\Core\ModuleCore {
	protected static $hasModel = false;

	private static $title = '';
	private static $metaDescription = '';

	private static $meta = [];
	private static $link = [];
	private static $base = ['url'=>'', 'target'=>''];
	private static $favicon = ['url'=>'', 'imageType'=>''];
	private static $canonicalUrl = '';
	private static $js = [];
	private static $css = [];
	private static $custom = ['top'=>[], 'bottom'=>[]];

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
		if(!empty(self::$setBy['title'])){
			Debug::alert('The title was set by ' . self::$setBy['title'] . '.', 'n');
		}

		self::addMeta(['name' => 'description', 'content' => self::$metaDescription]);
		
		if (!empty(self::$setBy['metaDescription'])) {
			Debug::alert('The title was set by ' . self::$setBy['metaDescription'] . '.', 'n');
		}

		self::$favicon['imageType'] = \Arembi\Xfw\Misc\getFileExtension(self::$favicon['url']);

		// Get the robots meta tag contents
		self::addMeta(['name' => 'robots', 'content' => self::generateRobotsMeta()]);
		
		// Adding the canonical link
		if (!empty(self::$canonicalUrl)) {
			$l = new Link(['href'=>self::$canonicalUrl]);
			self::addLink([
				['rel', 'canonical'],
				['href', Router::url(self::$canonicalUrl)]
			]);
		}

		$this->lv('title', self::$title);
		$this->lv('meta', self::$meta);
		$this->lv('css', self::$css);
		$this->lv('js', self::$js);
		$this->lv('custom', self::$custom);
		$this->lv('link', self::$link);
		$this->lv('base', self::$base);
		$this->lv('favicon', self::$favicon);
	}


	public static function title(?string $title = null)
	{
		if ($title !== null) {
			self::$title = $title;
		}
		return self::$title;
	}


	public static function metaDescription(?string $description = null)
	{
		if ($description !== null) {
			self::$metaDescription = $description;
		}
		return self::$metaDescription;
	}


	public static function baseUrl(?string $url = null)
	{
		if ($url !== null) {
			self::$baseUrl = $url;
		}
		return self::$baseUrl;
	}


	public static function faviconUrl(?string $url = null)
	{
		if ($url !== null) {
			self::$favicon['url'] = $url;
		}
		return self::$favicon['url'];
	}


	public static function addJs($js, bool $async = false)
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
				$ccss = str_replace(SITES_DIR, Router::getHostUrl(), $ccss);
				if (!in_array($ccss, self::$css)) {
					self::$css[] = $ccss;
				}
			}
		} elseif (is_string($css)) {
			$css = trim($css);
			$css = str_replace(SITES_DIR, Router::getHostUrl(), $css);
			if (!in_array($css, self::$css)) {
				self::$css[] = $css;
			}
		} else {
			Debug::alert('Not supported css: ' . print_r($css, true));
		}
	}


	// Metas should be arrays like this
	// [
	// 'attr1' => 'value1',
	// 'attr2' => 'value2'
	// ]
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


	public static function canonical(?string $url = null)
	{
		if ($url !== null) {
			self::$canonicalUrl = $url;
		}
		return self::$canonicalUrl;
	}


	public static function addCustomHtml(string $custom, string $position = 'bottom')
	{
		self::$custom[$position] = $custom;
	}


	public static function generateRobotsMeta()
	{
		$meta = [];
		$meta['index'] = Seo::indexable() ? 'index' : 'noindex';
		$meta['follow'] = Seo::followable() ? 'follow' : 'nofollow';
		$meta['noArchive'] = Seo::archivable() ? false : 'noarchive';
		$meta['maxSnippet'] = 'max-snippet:' . Seo::maxSnippet();
		$meta['maxImagePreview'] = 'max-image-preview:' . Seo::maxImagePreview();
		$meta['max-video-preview'] = 'max-video-preview:' . Seo::maxVideoPreview();
		$meta['noTranslate'] = Seo::translatable() ? false : 'notranslate';
		$meta['noImageIndex'] = Seo::imagesIndexable() ? false : 'noimageindex';

		$content = implode(', ', array_filter($meta));

		return $content;
	}
}
