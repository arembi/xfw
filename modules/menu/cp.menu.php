<?php

namespace Arembi\Xfw\Module;
use Arembi\Xfw\Core\Router;

class CP_MenuBase extends Menu {

	public static function menu()
	{
		return [
			'title' => [
				'hu' => 'Menük',
				'en' => 'Menus'
			],
			'items' => [
				['home', [
					'hu' => 'Info',
					'en' => 'Info']
				],
				['menu_list', [
					'hu' => 'Lista',
					'en' => 'List']
				],
				['menu_new', [
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
			A menu module is a collection of menuitems (links).
		</div>
	<?php
	}



	public function menu_list()
	{
		$menus = $this->model->getMenusByDomainId();

		foreach ($menus as &$menu) {
			$editLink = new Link(['anchor' => 'edit', 'href' => '?task=menu_edit&id=' . $menu->id]);
			$menu->editLink = $editLink->processLayout()->getLayoutHtml();
			$deleteLink = new Link(['anchor' => 'delete', 'href' => '?task=menu_delete&id=' . $menu->id]);
			$menu->deleteLink = $deleteLink->processLayout()->getLayoutHtml();
		}
		unset($menu);
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
					<th>Name</th>
					<th>Type</th>
					<th title="Created At">CA</th>
					<th title="Last Updated">UA</th>
					<th colspan="2">Tools</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach($menus as $i => $menu):?>
				<tr>
					<td title="ID"><?php echo $menu->id;?></td>
					<td title="name"><?php echo $menu->name?></td>
					<td title="type"><?php echo $menu->type?></td>
					<td title="created at"><?php echo $menu->createdAt?></td>
					<td title="last updated"><?php echo $menu->updatedAt?></td>
					<td title="edit"><?php echo $menu->editLink ?></td>
					<td title="delete"><?php echo $menu->deleteLink ?></td>
				</tr>
			<?php endforeach;?>
			</tbody>
		</table>
	<?php
	}



	public function menu_new()
	{
		$form = new Form(['handlerModule' => 'menu', 'handlerMethod' => 'menu_new'], false);

		// name
        $form->addField('name')
			->label('Name');

        // type
		$typeSelectOptions = [
            'p' => ['value' => 'p'],
            's' => ['value' => 's']
        ];
        $form->addField('type', 'select')
			->label('Type')
			->options($typeSelectOptions);

		$form
			->finalize()
			->processLayout()
			->render();
	}



	public function menu_edit()
	{
		$menu = $this->model->getMenuByMenuId(Router::get('id'));

		$form = new Form(['handlerModule' => 'menu', 'handlerMethod' => 'menu_update'], false);

		// ID
		$form->addField('id')
			->attributes(['value' => $menu->id, 'readonly' => true])
			->label('ID');

        // name
        $form->addField('name')
			->label('Name')
			->attribute('value', $menu->name);

		// type
		$typeSelectOptions = [
            'p' => ['value' => 'p'],
            's' => ['value' => 's']
        ];
		$typeSelectOptions[$menu->type]['selected'] = 'selected';

		$form->addField('type', 'select')
			->label('Type')
			->options($typeSelectOptions);

		// Created At
		$form->addField('createdAt')
			->attributes(['value' => $menu->createdAt, 'readonly' => true])
			->label('CA');

        // Updated At
		$form->addField('updatedAt')
			->attributes(['value' => $menu->updatedAt, 'readonly' => true])
			->label('UA');

		$form
			->finalize()
			->processLayout()
			->render();?>

		<table>
			<thead>
				<tr>
					<th>ID</th>
					<th>Type</th>
					<th>Label</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($menu->menuitems as $menuitem) :
				$menuitemLink = new Link(['anchor' => 'edit', 'href' => '?task=menuitem_edit&id=' . $menuitem->id]);
				
				if ($menuitem->item['type'] == 'menu') {
					$menu = $this->model->getMenuByMenuId($menuitem->item['id']);
					$label = $menu->name ?? 'submenu';
				} else {
					$link = new Link(['anchor'=>$menuitem->item['anchorText'], 'href'=>$menuitem->item['href']]);
					$label = $link->getHref();
				}
				?>

				<tr>
					<td><?php echo $menuitem->id ?></td>
					<td><?php echo $menuitem->item['type'] ?></td>
					<td><?php echo $label ?></td>
					<td><?php $menuitemLink->processLayout()->render() ?></td>
				</tr>
			<?php endforeach;?>
			</tbody>
			</table>
		<?php
	}


	public function menu_delete()
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


	public function menuitem_new()
	{
		
	}


	public function menuitem_edit()
	{
		$menu = $this->model->getMenuByMenuId(Router::get('id'));

	}


	public function menuitem_delete()
	{
		
	}
}
