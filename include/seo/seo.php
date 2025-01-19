<?php
namespace Arembi\Xfw;
use Arembi\Xfw\Module\Head;

abstract class Seo {

	private static $indexable;

	private static $followable;

	private static $archivable;

	private static $maxSnippet;

	private static $maxImagePreview;

	private static $allowedmaxImagePreviewSettings;

	private static $maxVideoPreview;

	private static $translatable;

	private static $imagesIndexable;


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


	public static function title($title = null)
	{
		if ($title === null) {
			Head::getTitle();
		} else {
			Head::setTitle($title);	
		}
	}


	public static function metaDescription($description = null)
	{
		if ($description === null) {
			Head::getMetaDescription();	
		} else {
			Head::setMetaDescription($description);
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


	public static function maxSnippet($characterCount = null)
	{
		if ($characterCount === null) {
			return self::$maxSnippet;
		} else {
			self::$maxSnippet = $characterCount;
		}
	}


	public static function maxImagePreview($setting = null)
	{
		if ($setting === null) {
			return self::$maxImagePreview;
		} else {
			if (in_array($setting, self::$allowedmaxImagePreviewSettings)) {
				self::$maxImagePreview = $setting;
			} else {
				Debug::alert('max-image-preview could not be set, invalid value given.', 'f');
			}
		}
	}


	public static function maxVideoPreview($seconds = null)
	{
		if ($seconds === null) {
			return self::$maxVideoPreview;
		} else {
			if ($seconds >= -1) {
				self::$maxVideoPreview = $seconds;
			} else {
				Debug::alert('max-video-preview could not be set, invalid value given.', 'f');
			}
		}
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

}
