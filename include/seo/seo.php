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


	public static function title(?string $title = null)
	{
		Head::title($title);
	}


	public static function metaDescription(?string $description = null)
	{
		Head::metaDescription($description);
	}


	public static function indexable(?bool $state = null)
	{
		if ($state !== null) {
			self::$indexable = $state;
		}
		return self::$indexable;
	}


	public static function followable(?bool $state = null)
	{
		if ($state !== null) {
			self::$followable = $state;
		}
		return self::$followable;
	}


	public static function archivable(?bool $state = null)
	{
		if ($state !== null) {
			self::$archivable = $state;
		}
		return self::$archivable;
	}


	public static function maxSnippet(?int $characterCount = null)
	{
		if ($characterCount !== null) {
			self::$maxSnippet = $characterCount;	
		}
		return self::$maxSnippet;
	}


	public static function maxImagePreview($setting = null)
	{
		if ($setting !== null) {
			if (in_array($setting, self::$allowedmaxImagePreviewSettings)) {
				self::$maxImagePreview = $setting;
			} else {
				Debug::alert('max-image-preview could not be set, invalid value given.', 'f');
			}
		}
		return self::$maxImagePreview;
	}


	public static function maxVideoPreview(?int $seconds = null)
	{
		if ($seconds !== null) {
			if ($seconds >= -1) {
				self::$maxVideoPreview = $seconds;
			} else {
				Debug::alert('max-video-preview could not be set, invalid value given.', 'f');
			}	
		}
		return self::$maxVideoPreview;
	}


	public static function translatable(?bool $state = null)
	{
		if ($state !== null) {
			self::$translatable = $state;
		}
		return self::$translatable;
	}


	public static function imagesIndexable(?bool $state = null)
	{
		if ($state !== null) {
			self::$imagesIndexable = $state;
		}
		return self::$imagesIndexable;
	}


	public static function canonical(?string $url = null)
	{
		Head::canonical($url);
	}

}
