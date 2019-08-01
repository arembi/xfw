<?php

namespace Arembi\Xfw\Module;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Misc;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Core\Models\Static_Page;

class Static_PageBaseModel {

	// Returns static page records
	// All if $pageIDs was not given, or only the records with requested IDs
	public function getPages(array $pageIDs = [])
	{
		$pages = DB::table('static_pages')
			->leftJoin('users', 'users.id', '=', 'static_pages.created_by')
			->leftJoin('seo_module', 'seo_module.foreign_id', '=', 'static_pages.id')
			->leftJoin('modules', 'modules.id', '=', 'seo_module.module_id')
			->leftJoin('seo', 'seo.id', '=', 'seo_module.seo_id')
			->select(
				'static_pages.id as ID',
				'static_pages.route_id as routeID',
				'static_pages.title as pageTitle',
				'static_pages.content as pageContent',
				'static_pages.created_at AS createdAt',
				'static_pages.created_by as createdBy',
				'static_pages.updated_at as updatedAt',
				'users.username as username',
				'seo.title as seoTitle',
				'seo.description as seoDescription',
				'seo.keywords as seoKeywords',
				'seo.head_end as seoHeadEnd',
				'seo.body_begin as seoBodyBegin',
				'seo.body_end as seoBodyEnd'
				)
			->get();

		if (count($pageIDs) > 0) {
			$pages = $pages->filter(function ($page) use ($pageIDs){
				return in_array($page->ID, $pageIDs) ;
			});
		}

		$pages->transform(function($page){
			$date = new \DateTime($page->createdAt);
			$page->createdAt = $date->format(Settings::_('dateTimeFormat')[App::getLang()]);

			if ($page->updatedAt !== null) {
				$date = new \DateTime($page->updatedAt);
				$page->updatedAt = $date->format(Settings::_('dateTimeFormat')[App::getLang()]);
			}

			$page->pageTitle = json_decode($page->pageTitle, true);
			$page->pageContent = json_decode($page->pageContent, true);
			return $page;
		});

		return $pages;
	}


	public function getPageByID($pageID)
	{
		return $this->getPages([$pageID])->first();
	}


	public function getPageByRouteID($routeID)
	{
		$page = DB::table('static_pages')
			->leftJoin('users', 'users.id', '=', 'static_pages.created_by')
			->leftJoin('seo_module', 'seo_module.foreign_id', '=', 'static_pages.id')
			->leftJoin('modules', 'modules.id', '=', 'seo_module.module_id')
			->leftJoin('seo', 'seo.id', '=', 'seo_module.seo_id')
			->select(
				'static_pages.id as ID',
				'static_pages.route_id as routeID',
				'static_pages.title as pageTitle',
				'static_pages.content as pageContent',
				'static_pages.created_at as createdAt',
				'static_pages.created_by as createdBy',
				'static_pages.updated_at as updatedAt',
				'users.username as username',
				'seo.title as seoTitle',
				'seo.description as seoDescription',
				'seo.keywords as seoKeywords',
				'seo.head_end as seoHeadEnd',
				'seo.body_begin as seoBodyBegin',
				'seo.body_end as seoBodyEnd'
				)
			->where('static_pages.route_id', $routeID)
			->first();

			if ($page) {
				$date = new \DateTime($page->createdAt);
				$page->createdAt = $date->format(Settings::_('dateTimeFormat')[App::getLang()]);

				if ($page->updatedAt !== null) {
					$date = new \DateTime($page->updatedAt);
					$page->updatedAt = $date->format(Settings::_('dateTimeFormat')[App::getLang()]);
				}

				$page->pageTitle = json_decode($page->pageTitle, true);
				$page->pageContent = json_decode($page->pageContent, true);
			}

			return $page;
	}


	public function newPage(array $pageData)
	{
		$page = new Static_Page();

		$page->route_id = $pageData['routeID'];
		$page->title = $pageData['title'];
		$page->content = $pageData['content'];
		$page->created_by = $pageData['createdBy'];

		return $page->save();
	}


	public function updatePage(array $pageData)
	{
		$page = Static_Page::find($pageData['ID']);

		$page->route_id = $pageData['routeID'];
		$page->title = $pageData['title'];
		$page->content = $pageData['content'];
		$page->created_by = $pageData['createdBy'];

		return $page->save();
	}



	public function deletePage($ID)
	{
		$page = Static_Page::find($ID);

		if ($page) {
			return $page->delete();
		} else {
			return false;
		}

	}

}
