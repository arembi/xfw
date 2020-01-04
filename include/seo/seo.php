<?php
namespace Arembi\Xfw;
use Arembi\Xfw\Module\Head;

abstract class Seo {

	// https://developers.google.com/search/reference/robots_meta_tag
	private static $indexable;

	private static $followable;

	private static $archivable;

	private static $maxSnippet;

	private static $maxImagePreview;

	private static $allowedmaxImagePreviewSettings;

	private static $maxVideoPreview;

	private static $translatable;

	private static $imagesIndexable;

	// content of the virtual robots.txt file
	private static $robotsTxt;

	// content of the virtual sitemap.xml file
	private static $sitemapXML;


	public static function init()
	{
		self::$indexable = true;
		self::$followable = true;
		self::$archivable = true;
		self::$maxSnippet = -1;
		self::$maxImagePreview = 'standard';
		self::$allowedmaxImagePreviewSettings = [
			'standard',
			'large',
			'none'
		];
		self::$maxVideoPreview = -1;
		self::$translatable = true;
		self::$imagesIndexable = true;
	}


	public static function title($title = false)
	{
		if (!empty($title)) {
			Head::setTitle($title);
		} else {
			Head::getTitle();
		}
	}


	public static function metaDescription($description = false)
	{
		if (!empty($description)) {
			Head::setMetaDescription($description);
		} else {
			Head::getMetaDescription();
		}
	}


	public function indexable(bool $state)
	{
		self::$indexable = $state;
	}


	public static function isIndexable()
	{
		return self::$indexable;
	}


	public static function followable(bool $state)
	{
		self::$followable = $state;
	}


	public static function isFollowable()
	{
		return self::$followable;
	}


	public static function archivable(bool $state)
	{
		self::$archivable = $state;
	}


	public static function isArchivable()
	{
		return self::$archivable;
	}


	public static function maxSnippet(int $characterCount)
	{
		self::$maxSnippet = $characterCount;
	}


	public static function getMaxSnippet()
	{
		return self::$maxSnippet;
	}


	public static function maxImagePreview(string $setting)
	{
		if (in_array($setting, self::$allowedmaxImagePreviewSettings)) {
			self::$maxImagePreview = $setting;
		} else {
			Debug::alert('max-image-preview could not be set, invalid value given', 'f');
		}
	}


	public static function getMaxImagePreview()
	{
		return self::$maxImagePreview;
	}


	public static function maxVideoPreview(int $seconds)
	{
		if ($seconds >= -1) {
			self::$maxVideoPreview = $seconds;
		} else {
			Debug::alert('max-video-preview could not be set, invalid value given', 'f');
		}
	}


	public static function getMaxVideoPreview()
	{
		return self::$maxVideoPreview;
	}


	public static function translatable(bool $state)
	{
		self::$translatable = $state;
	}


	public static function isTranslatable()
	{
		return self::$translatable;
	}


	public static function imagesIndexable(bool $state)
	{
		self::$imagesIndexable = $state;
	}


	public static function areImagesIndexable()
	{
		return self::$imagesIndexable;
	}


	public static function canonical($href)
	{
		Head::canonical($href);
	}


	public static function generateSitemapXml()
	{

	}



	public static function generateRobotsTxt()
	{

	}



}
