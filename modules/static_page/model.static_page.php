<?php

namespace Arembi\Xfw\Module;

use Illuminate\Database\Capsule\Manager as DB;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Settings;
use Arembi\Xfw\Core\Models\Static_Page;
use DateTime;

class Static_PageBaseModel {

	// Returns static page records
	// All, if $pageIds was not given, or only the records with requested IDs
	public function getPages(array $pageIds = [])
	{
		$pages = DB::table('static_pages')
			->leftJoin('users', 'users.id', '=', 'static_pages.created_by')
			->leftJoin('seo_module', 'seo_module.foreign_id', '=', 'static_pages.id')
			->leftJoin('modules', 'modules.id', '=', 'seo_module.module_id')
			->leftJoin('seo', 'seo.id', '=', 'seo_module.seo_id')
			->select(
				'static_pages.id as id',
				'static_pages.route_id as routeId',
				'static_pages.title as pageTitle',
				'static_pages.excerpt as pageExcerpt',
				'static_pages.content as pageContent',
				'static_pages.thumbnail as pageThumbnail',
				'static_pages.created_at AS createdAt',
				'static_pages.created_by as createdBy',
				'static_pages.updated_at as updatedAt',
				'users.username as username',
				'seo.title as seoTitle',
				'seo.description as seoDescription',
				'seo.head_end as seoHeadEnd',
				'seo.body_begin as seoBodyBegin',
				'seo.body_end as seoBodyEnd'
				)
			->get();

		if (count($pageIds) > 0) {
			$pages = $pages->filter(function ($page) use ($pageIds){
				return in_array($page->id, $pageIds) ;
			});
		}

		$pages->transform(function ($page) {
			return $this->normalize($page);
		});
		
		return $pages;
	}


	public function getPagesByDomainId(int $domainId)
	{
		$pages = DB::table('static_pages')
			->leftJoin('routes', 'routes.id', '=', 'static_pages.route_id')
			->leftJoin('users', 'users.id', '=', 'static_pages.created_by')
			->leftJoin('seo_module', 'seo_module.foreign_id', '=', 'static_pages.id')
			->leftJoin('modules', 'modules.id', '=', 'seo_module.module_id')
			->leftJoin('seo', 'seo.id', '=', 'seo_module.seo_id')
			->select(
				'static_pages.id as id',
				'static_pages.route_id as routeId',
				'static_pages.title as pageTitle',
				'static_pages.excerpt as pageExcerpt',
				'static_pages.content as pageContent',
				'static_pages.thumbnail as pageThumbnail',
				'static_pages.created_at AS createdAt',
				'static_pages.created_by as createdBy',
				'static_pages.updated_at as updatedAt',
				'users.username as username',
				'seo.title as seoTitle',
				'seo.description as seoDescription',
				'seo.head_end as seoHeadEnd',
				'seo.body_begin as seoBodyBegin',
				'seo.body_end as seoBodyEnd'
				)
			->where('routes.domain_id', '=', $domainId)
			->get();
		
		$pages->transform(function ($page) {
			return $this->normalize($page);
		});

		return $pages;
	}


	public function getPageById($pageId)
	{
		return $this->getPages([$pageId])->first();
	}


	public function getPageByRouteId($routeId)
	{
		$page = DB::table('static_pages')
			->leftJoin('users', 'users.id', '=', 'static_pages.created_by')
			->leftJoin('seo_module', 'seo_module.foreign_id', '=', 'static_pages.id')
			->leftJoin('modules', 'modules.id', '=', 'seo_module.module_id')
			->leftJoin('seo', 'seo.id', '=', 'seo_module.seo_id')
			->select(
				'static_pages.id as id',
				'static_pages.route_id as routeId',
				'static_pages.title as pageTitle',
				'static_pages.excerpt as pageExcerpt',
				'static_pages.content as pageContent',
				'static_pages.thumbnail as pageThumbnail',
				'static_pages.created_at as createdAt',
				'static_pages.created_by as createdBy',
				'static_pages.updated_at as updatedAt',
				'users.username as creator',
				'seo.title as seoTitle',
				'seo.description as seoDescription',
				'seo.head_end as seoHeadEnd',
				'seo.body_begin as seoBodyBegin',
				'seo.body_end as seoBodyEnd'
				)
			->where('static_pages.route_id', $routeId)
			->first();

		if ($page) {
			$page = $this->normalize($page);
		}

		return $page;
	}


	private function normalize($page)
	{
		$date = new DateTime($page->createdAt ?? 'now');
		$page->createdAt = $date->format(Settings::get('dateTimeFormat')[App::getLang()]);

		if ($page->updatedAt !== null) {
			$date = new DateTime($page->updatedAt);
			$page->updatedAt = $date->format(Settings::get('dateTimeFormat')[App::getLang()]);
		}

		$page->pageTitle = json_decode($page->pageTitle ?? '', true);
		$page->pageExcerpt = json_decode($page->pageExcerpt ?? '', true);
		$page->pageContent = json_decode($page->pageContent ?? '', true);
		$page->seoTitle = json_decode($page->seoTitle ?? '', true);
		$page->seoDescription = json_decode($page->seoDescription ?? '', true);
		
		return $page;
	}


	public function addPage(array $pageData)
	{
		$page = new Static_Page();

		$page->route_id = $pageData['routeId'];
		$page->title = $pageData['title'];
		$page->excerpt = $pageData['excerpt'];
		$page->content = $pageData['content'];
		$page->thumbnail = $pageData['thumbnail'];
		$page->created_by = $pageData['createdBy'];

		return $page->save();
	}


	public function updatePage(array $pageData)
	{
		$page = Static_Page::find($pageData['id']);

		$page->route_id = $pageData['routeId'];
		$page->title = $pageData['title'];
		$page->excerpt = $pageData['excerpt'];
		$page->content = $pageData['content'];
		$page->thumbnail = $pageData['thumbnail'];
		$page->created_by = $pageData['createdBy'];

		return $page->save();
	}


	public function deletePage(int $id)
	{
		$page = Static_Page::find($id);

		return $page ? $page->delete() : false;
	}

}