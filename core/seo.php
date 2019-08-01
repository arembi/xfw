<?php

namespace Arembi\Xfw\Core;
use Arembi\Xfw\Module\HEAD;

abstract class SEO {
	// content of the virtual robots.txt file
	private static $robotsTXT;

	// content of the virtual sitemap.xml file
	private static $sitemapXML;

	// Google Search Console tag
	private static $googleSC;

	// Google Analytics tracking script
	private static $googleAN;



	public static function googleInit()
	{
		if (self::$googleSC && !IS_LOCALHOST) {
			HEAD::addMeta([['google-site-verification', self::$googleSC]]);
		}

		if (self::$googleAN && !IS_LOCALHOST) {
			HEAD::addJS(self::$googleAN);
		}

	}



	public static function title($title = false, $setBy = false)
	{
		if (!empty($title)) {
			HEAD::setTitle($title, $setBy);
		} else {
			HEAD::getTitle();
		}

	}


	public static function description($description = false, $setBy = false)
	{
		if (!empty($description)) {
			HEAD::setMetaDescription($description, $setBy);
		} else {
			HEAD::getMetaDescription();
		}
	}



	public static function keywords($keywords = false, $setBy = false)
	{
		if (!empty($keywords)) {
			HEAD::setMetaKeywords($keywords, $setBy);
		} else {
			HEAD::getMetaKeywords();
		}
	}



	public static function setGoogleSC($googleSC)
	{
		self::$googleSC = $googleSC;
	}



	public static function setGoogleAN($googleAN)
	{
		self::$googleAN = $googleAN;
	}



	public static function noIndex($noFollow = true)
	{
		$content = $noFollow === true ? 'noindex, nofollow' : 'noindex';

		HEAD::addMeta(['name' => 'robots', 'content' => $content]);
	}



	public static function canonical($href, $setBy = false)
	{
		HEAD::canonical($href, $setBy);
	}



	public static function generateSitemapXml()
	{

	}



	public static function generateRobotsTxt()
	{

	}



}
