<?php

/*
The <head>
	The head will be sent to the output after the rest of the embedded modules.
	You can set f.i. the <title> anywhere in the system by calling the
	Head::title('your title') function.
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
	protected static $autoloadModel = false;

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


	protected function init() {}


	public function finalize(): void
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

		$jsTags = [];

		foreach (self::$js as $j) {
			$attributes = [
				'src'=>$j->src(),
				'type'=>$j->type(),
				'async'=>$j->async(),
				'defer'=>$j->defer(),
				'crossorigin'=>$j->crossorigin(),
				'integrity'=>$j->integrity(),
				'nomodule'=>$j->nomodule(),
				'referrerpolicy'=>$j->referrerpolicy()
			];
			$jsTags[] = [
				'attributes'=>parseHtmlAttributes($attributes),
				'content'=>$j->content()
			];
		}

		$this->lv('title', self::$title);
		$this->lv('meta', self::$meta);
		$this->lv('css', self::$css);
		$this->lv('javascript', $jsTags);
		$this->lv('custom', self::$custom);
		$this->lv('link', self::$link);
		$this->lv('base', self::$base);
		$this->lv('favicon', self::$favicon);
	}


	public static function title(string|array|null $title = null)
	{
		if ($title === null) {
			return self::$title;
		}
		self::$title = $title;
	}


	public static function metaDescription(string|array|null $description = null)
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
		self::$baseUrl = Router::url($url);
	}


	public static function faviconUrl(?string $url = null)
	{
		if ($url === null) {
			return self::$favicon['url'];
		}
		self::$favicon['url'] = Router::url($url);
	}


	public static function canonical(?string $url = null)
	{
		if ($url === null) {
			return self::$canonicalUrl;
		}
		self::$canonicalUrl = Router::url($url);
	}


	public static function addJs(
		string $src = '',
		string $type = '',
		bool $async = false,
		bool $defer = false,
		string $crossorigin = '',
		string $integrity = '',
		string $nomodule = '',
		string $referrerpolicy = '',
		string $content = ''
	): void
	{
		self::$js[] = new Js(
			$src,
			$type,
			$async,
			$defer,
			$crossorigin,
			$integrity,
			$nomodule,
			$referrerpolicy,
			$content
		);
	}


	public static function addCss(string $url): void
	{
		$url = trim($url);
		$url = Router::url($url);
		if (!in_array($url, self::$css)) {
			self::$css[] = $url;
		}
	}


	public static function removeCss(int $key): void
	{
		unset(self::$css[$key]);
	}


	// Metas should be arrays like this
	// [
	// 'attr1' => 'value1',
	// 'attr2' => 'value2'
	// ]
	public static function addMeta(array $meta): void
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


	public static function removeMeta(string $type, ?string $key = null): void
	{
		if ($key === null) {
			unset(self::$meta[$type]);
		} else {
			unset(self::$meta[$type][$key]);
		}
	}


	public static function addLink(array $attributes): void
	{
		array_walk($attributes, fn($e) => htmlspecialchars($e));
		self::$link[] = parseHtmlAttributes($attributes);
	}


	public static function removeLink(int $key): void
	{
		unset(self::$link[$key]);
	}


	public static function addCustomHtml(string $custom, string $position = 'bottom'): void
	{
		self::$custom[$position] = $custom;
	}


	public static function removeCustomHtml(string $position, int $key): void
	{
		unset(self::$custom[$position][$key]);
	}


	public static function generateRobotsMeta(): array
	{
		return [
			'index'=>Seo::indexable() ? 'index' : 'noindex',
			'follow'=>Seo::followable() ? 'follow' : 'nofollow',
			'noArchive'=>Seo::archivable() ? false : 'noarchive',
			'maxSnippet'=>'max-snippet:' . Seo::maxSnippet(),
			'maxImagePreview'=>'max-image-preview:' . Seo::maxImagePreview(),
			'max-video-preview'=>'max-video-preview:' . Seo::maxVideoPreview(),
			'noTranslate'=>Seo::translatable() ? false : 'notranslate',
			'noImageIndex'=>Seo::imagesIndexable() ? false : 'noimageindex'
		];
	}
}
