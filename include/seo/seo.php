<?php

namespace Arembi\Xfw\Inc;

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
		self::$allowedmaxImagePreviewSettings = [
			'standard',
			'large',
			'none'
		];
		self::$maxImagePreview = 'standard';
		self::$maxVideoPreview = -1;
		self::$translatable = true;
		self::$imagesIndexable = true;
	}


	public static function title(string|array|null $title): void
	{
		Head::title($title);
	}


	public static function metaDescription(?string $description = null):void
	{
		Head::metaDescription($description);
	}


	public static function indexable(?bool $state = null)
	{
		if ($state === null) {
			return self::$indexable;
		}
		self::$indexable = $state;
	}


	public static function followable(?bool $state = null)
	{
		if ($state === null) {
			return self::$followable;
		}
		self::$followable = $state;
	}


	public static function archivable(?bool $state = null)
	{
		if ($state === null) {
			return self::$archivable;
		}
		self::$archivable = $state;
	}


	public static function maxSnippet(?int $characterCount = null)
	{
		if ($characterCount === null) {
			return self::$maxSnippet;
		}
		self::$maxSnippet = $characterCount;
	}


	public static function maxImagePreview($setting = null)
	{
		if ($setting === null) {
			return self::$maxImagePreview;
		}
		if (in_array($setting, self::$allowedmaxImagePreviewSettings)) {
			self::$maxImagePreview = $setting;
		} else {
			Debug::alert('max-image-preview could not be set, invalid value given.', 'f');
		}
	}


	public static function maxVideoPreview(?int $seconds = null)
	{
		if ($seconds === null) {
			return self::$maxVideoPreview;
		}
		if ($seconds >= -1) {
			self::$maxVideoPreview = $seconds;
		} else {
			Debug::alert('max-video-preview could not be set, invalid value given.', 'f');
		}	
	}


	public static function translatable(?bool $state = null)
	{
		if ($state === null) {
			return self::$translatable;
		}
		self::$translatable = $state;
	}


	public static function imagesIndexable(?bool $state = null)
	{
		if ($state === null) {
			return self::$imagesIndexable;
		}
		self::$imagesIndexable = $state;
	}


	public static function canonical(?string $url = null)
	{
		Head::canonical($url);
	}

}
