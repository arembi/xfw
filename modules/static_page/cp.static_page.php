<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Router;

class CP_Static_PageBase extends Static_Page {

	public static $cpMenu = [
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
		$pages = $this->model->getPagesByDomainID(DOMAIN_ID);

		$avLangs = \Arembi\Xfw\Core\Settings::get('availableLanguages');

		foreach ($pages as &$page) {
			$editLink = new Link(['anchor' => 'edit', 'href' => '?task=page_edit&id=' . $page->ID]);
			$page->editLink = $editLink->processLayout()->getLayoutHTML();
			$deleteLink = new Link(['anchor' => 'delete', 'href' => '?task=page_delete&id=' . $page->ID]);
			$page->deleteLink = $deleteLink->processLayout()->getLayoutHTML();
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
					<td title="ID"><?php echo $page->ID;?></td>
					<td title="routeID"><?php echo $page->routeID?></td>
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
									<?php echo Router::getRouteByID($page->routeID, $lang[0]) ?? 'not set'?>
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
			$form->addField('pageTitle-' . $lang[0]);
			$form->setFieldLabel('pageTitle-' . $lang[0], 'Cím (' . $lang[0] . ')');
		}
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$form->addField('pageContent-' . $lang[0], 'textarea');
			$form->setFieldLabel('pageContent-' . $lang[0], 'Content (' . $lang[0] . ')');
		}

		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$createdBySelectOptions[$user->username] = ['value' => $user->ID];
		}
		$form->addField('createdBy', 'select');
		$form->setFieldLabel('createdBy', 'Creator');
		$form->setFieldOptions('createdBy', $createdBySelectOptions);


		$routeIDSelectOptions = [
			"[unpublished]"=>['value'=>0]
		];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$routeIDSelectOptions[$option] = ['value' => $id];
		}

		$form->addField('routeID', 'select');
		$form->setFieldLabel('routeID', 'Path');
		$form->setFieldOptions('routeID', $routeIDSelectOptions);

		$form->build();
		$form->render();
	}



	public function page_edit()
	{
		$page = $this->model->getPageByID(Router::$GET['id']);

		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_update'], false);

		// ID
		$form->addField('ID');
		$form->setFieldAttributes('ID', ['value' => $page->ID, 'readonly' => true]);
		$form->setFieldLabel('ID', 'ID');

		// pageTitle
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$form->addField('pageTitle-' . $lang[0]);
			$form->setFieldLabel('pageTitle-' . $lang[0], 'Cím (' . $lang[0] . ')');
			$pageTitle = $page->pageTitle[$lang[0]] ?? '';
			$form->setFieldAttribute('pageTitle-' . $lang[0], 'value', $pageTitle);
		}

		// pageContent
		foreach (\Arembi\Xfw\Core\Settings::get('availableLanguages') as $lang) {
			$form->addField('pageContent-' . $lang[0], 'textarea');
			$form->setFieldLabel('pageContent-' . $lang[0], 'Tartalom (' . $lang[0] . ')');
			$pageContent = $page->pageContent[$lang[0]] ?? '';
			$form->setFieldText('pageContent-' . $lang[0], $pageContent);
		}

		// createdBy
		$createdBySelectOptions = [];
		foreach (App::getUsersByDomain() as $user) {
			$attributes = ['value' => $user->ID];
			if ($user->ID === $page->createdBy) {
				$attributes['selected'] = 'selected';
			}
			$createdBySelectOptions[$user->username] = $attributes;
		}
		$form->addField('createdBy', 'select');
		$form->setFieldLabel('createdBy', 'Szerző');
		$form->setFieldOptions('createdBy', $createdBySelectOptions);

		// routeID
		$routeIDSelectOptions = ['none' => ['value' => 0]];
		foreach (Router::getRoutes('primary') as $id => $route) {
			$option = is_array($route->path) ? implode(' | ', $route->path) : $route->path;
			$attributes = ['value' => $id];
			if ($page->routeID == $id) {
				$attributes['selected'] = 'selected';
			}
			$routeIDSelectOptions[$option] = $attributes;
		}

		$form->addField('routeID', 'select');
		$form->setFieldLabel('routeID', 'Útvonal');
		$form->setFieldOptions('routeID', $routeIDSelectOptions);

		// Created At
		$form->addField('createdAt');
		$form->setFieldAttribute('createdAt', 'value', $page->createdAt);
		$form->setFieldLabel('createdAt', 'CA');

		$form->build();
		$form->render();
	}


	public function page_delete()
	{
		$ID = Router::$GET['id'];

		$form = new Form(['handlerModule' => 'static_page', 'handlerMethod' => 'page_delete']);
		$form->addField('ID');
		$form->setFieldAttributes('ID', ['value' => $ID, 'readonly' => true]);
		$form->setFieldLabel('ID', 'ID');

		$form->build();
		$form->render();

	}
}
