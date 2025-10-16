<?php

/*
The <head>
	The head will be sent to the output after the rest of the embedded modules.
	You can set f.i. the title anywhere in the system by calling the
	Head::setTitle('your title') function.
	Same goes to the other head elements: css, JS, meta tags.
*/

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Inc\Seo;
use Arembi\Xfw\Inc\Js;
use function Arembi\Xfw\Misc\getFileExtension;
use function Arembi\Xfw\Misc\parseHtmlAttributes;

class HeadBase extends ModuleBase {
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


	protected function init()
	{
		
	}


	public function finalize()
	{	
		$robotsMeta = self::generateRobotsMeta();
		$robotsMetaContent = implode(', ', array_filter($robotsMeta));
		
		self::addMeta(['name' => 'robots', 'content' => $robotsMetaContent]);
		self::addMeta(['name' => 'description', 'content' => self::$metaDescription]);
		
		if (!empty(self::$canonicalUrl)) {
			self::addLink([
				'rel'=>'canonical',
				'href'=>Router::url(self::$canonicalUrl)
			]);
		}

		if (!empty(self::$favicon['url'])) {
			self::$favicon['imageType'] = getFileExtension(self::$favicon['url']);
			self::addLink([
				'rel'=>'icon',
				'type'=>'image/' . self::$favicon['imageType'],
				'href'=> self::$favicon['url']
			]);
		}

		if(!empty(self::$setBy['title'])){
			Debug::alert('The title has been set by ' . self::$setBy['title'] . '.', 'n');
		}
		
		if (!empty(self::$setBy['metaDescription'])) {
			Debug::alert('The meta description has been set by ' . self::$setBy['metaDescription'] . '.', 'n');
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
		if ($title === null) {
			return self::$title;
		}
		self::$title = $title;
	}


	public static function metaDescription(?string $description = null)
	{
		if ($description == null) {
			return self::$metaDescription;
		}
		self::$metaDescription = $description;
	}


	public static function baseUrl(?string $url = null)
	{
		if ($url === null) {
			return self::$baseUrl;
		}
		self::$baseUrl = $url;
	}


	public static function faviconUrl(?string $url = null)
	{
		if ($url === null) {
			return self::$favicon['url'];
		}
		self::$favicon['url'] = $url;
	}


	public static function canonical(?string $url = null)
	{
		if ($url === null) {
			return self::$canonicalUrl;
		}
		self::$canonicalUrl = $url;
	}


	public static function addJs(string $type, string $content, bool $async = false)
	{
		self::$js[] = new Js($type, $content, $async);
	}


	public static function addCss($css)
	{
		$css = trim($css);
		$css = Router::url($css);
		if (!in_array($css, self::$css)) {
			self::$css[] = $css;
		}
	}


	public static function removeCss(int $key)
	{
		unset(self::$css[$key]);
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


	public static function removeMeta(string $type, ?string $key = null)
	{
		if ($key === null) {
			unset(self::$meta[$type]);
		} else {
			unset(self::$meta[$type][$key]);
		}
	}


	public static function addLink(array $attributes)
	{
		array_walk($attributes, fn($e) => htmlspecialchars($e));
		self::$link[] = parseHtmlAttributes($attributes);
	}


	public static function removeLink(int $key)
	{
		unset(self::$link[$key]);
	}


	public static function addCustomHtml(string $custom, string $position = 'bottom')
	{
		self::$custom[$position] = $custom;
	}


	public static function removeCustomHtml(string $position, int $key)
	{
		unset(self::$custom[$position][$key]);
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

		return $meta;
	}
}
