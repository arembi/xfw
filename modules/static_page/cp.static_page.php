<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;

class CP_Static_PageBase extends Static_Page {

	public static function menu()
	{
		return [
			'title' => [
				'hu' => 'Statikus Oldalak',
				'en' => 'Static Pages'
			],
			'items' => [
				['home', [
					'hu' => 'Info',
					'en' => 'Info']
				],
				['page_list', [
					'hu' => 'Lista',
					'en' => 'List']
				],
				['page_new', [
					'hu' => 'Új',
					'en' => 'New']
				]
			]
		];
	}

	public function home()
	{
	?>
		<div>
			The static page module can be used to display content with little or no dynamic parts, and which do not require to be organized into hierarchy.
		</div>
	<?php
	}



	public function page_list()
	{
		$pages = $this->model->getPagesByDomainId(DOMAIN_ID);

		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		foreach ($pages as &$page) {
			$editLink = new Link(['anchor' => 'edit', 'href' => '?task=page_edit&id=' . $page->id]);
			$page->editLink = $editLink->processLayout()->getLayoutHtml();
			$deleteLink = new Link(['anchor' => 'delete', 'href' => '?task=page_delete&id=' . $page->id]);
			$page->deleteLink = $deleteLink->processLayout()->getLayoutHtml();
		}
		unset($page);
		?>
		<style>
			td, th {
				border: 1px dotted #444;
			}
		</style>
		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Route ID</th>
					<th>Contents</th>
					<th title="Created By">CB</th>
					<th title="Created At">CA</th>
					<th title="Last Updated">UA</th>
					<th colspan="2">Tools</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($pages as $i => $page):?>
				<tr>
					<td title="ID"><?php echo $page->id;?></td>
					<td title="route ID"><?php echo $page->routeId?></td>
					<td title="content">
						<table>
							<tr>
								<th>Language</th>
								<th>Route</th>
								<th>Title</th>
								<th>Content</th>
							</tr>
						<?php foreach ($avLangs as $lang) :?>
							<tr>
								<td><?php echo $lang[0] ?>:</td>
								<td>
									<?php echo Router::getRouteById($page->routeId, $lang[0]) ?? 'not set'?>
								</td>
								<td>
									<?php echo !empty($page->pageTitle[$lang[0]]) ? $page->pageTitle[$lang[0]] : 'not set'?>
								</td>
								<td>
									<?php echo !empty($page->pageContent[$lang[0]]) ? 'set' : 'not set'?>
								</td>
							</tr>
						<?php endforeach;?>
						</table>
					</td>
					<td title="created by"><?php echo $page->username?></td>
					<td title="created at"><?php echo $page->createdAt?></td>
					<td title="last updated"><?php echo $page->updatedAt?></td>
					<td title="edit"><?php echo $page->editLink ?></td>
					<td title="delete"><?php echo $page->deleteLink ?></td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
	<?php
	}



	public function page_new()
	{
		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_add'], false);

		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$form->addField('pageTitle-' . $lang[0])
				->label('Cím (' . $lang[0] . ')');
		}
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$form->addField('pageContent-' . $lang[0], 'textarea')
				->label('Content (' . $lang[0] . ')');
		}

		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$createdBySelectOptions[$user->username] = ['value' => $user->id];
		}
		$form->addField('createdBy', 'select')
			->label('Creator')
			->options($createdBySelectOptions);


		$routeIdSelectOptions = [
			"[unpublished]"=>['value'=>0]
		];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$routeIdSelectOptions[$option] = ['value' => $id];
		}

		$form->addField('routeId', 'select')
			->label('Path')
			->options($routeIdSelectOptions);

		$form
			->finalize()
			->processLayout()
			->render();
	}



	public function page_edit()
	{
		$page = $this->model->getPageById(Router::get('id'));

		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_update'], false);

		// ID
		$form->addField('id')
			->attributes(['value' => $page->id, 'readonly' => true])
			->label('ID');

		// pageTitle
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$pageTitle = $page->pageTitle[$lang[0]] ?? '';
			
			$form->addField('pageTitle-' . $lang[0])
				->label('Cím (' . $lang[0] . ')')
				->attribute('value', $pageTitle);
		}

		// pageContent
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$pageContent = $page->pageContent[$lang[0]] ?? '';
			
			$form->addField('pageContent-' . $lang[0], 'textarea')
				->label('Tartalom (' . $lang[0] . ')')
				->text($pageContent);
		}

		// createdBy
		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$attributes = ['value' => $user->id];
			if ($user->id === $page->createdBy) {
				$attributes['selected'] = 'selected';
			}
			$createdBySelectOptions[$user->username] = $attributes;
		}
		$form->addField('createdBy', 'select')
			->label('Szerző')
			->options($createdBySelectOptions);

		// routeID
		$routeIdSelectOptions = ['none' => ['value' => 0]];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$attributes = ['value' => $id];
			if ($page->routeId == $id) {
				$attributes['selected'] = 'selected';
			}
			$routeIdSelectOptions[$option] = $attributes;
		}

		$form->addField('routeId', 'select')
			->label('Útvonal')
			->options($routeIdSelectOptions);

		// Created At
		$form->addField('createdAt')
			->attribute('value', $page->createdAt)
			->label('CA');

		$form
			->finalize()
			->processLayout()
			->render();
	}


	public function page_delete()
	{
		$id = Router::get('id');

		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_delete']);
		
		$form->addField('id')
			->attributes(['value' => $id, 'readonly' => true])
			->label('ID');

		$form
			->finalize()
			->processLayout()
			->render();

	}
}
