<?php
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Misc;
use Arembi\Xfw\Inc\CustomMenuitem;
use Illuminate\Database\Eloquent\Collection;

class MenuBase extends \Arembi\Xfw\Core\ModuleCore {

	protected static $hasModel = true;
	
	private $level;
	private $items;
	private $menuName;
	private $menuType;
	private $title;
	private $displayTitle;
	private $autoBuild;


	protected function main()
	{
		$this->loadModel();
		
		// The parameter should be used to preload a menu
		$key = isset($this->params['id']) ? 'id' : (isset($this->params['menuName']) ? 'name' : null);
		
		$this->level = $this->params['level'] ?? 0;
		$this->items = $this->params['items'] ?? new Collection();
		$this->title = $this->params['title'] ?? '';
		$this->displayTitle = $this->params['displayTitle'] ?? false;
		$this->autoBuild = $this->params['autoBuild'] ?? false;

		// Load stored data if requested based on the given key
		if ($key !== null) {
			$storedMenu = $key =='id'
				? $this->model->getMenuByMenuId($this->params['id'])
				: $this->model->getMenuByMenuName($this->params['menuName']);

			if ($storedMenu) {
				$this->menuName = $storedMenu->name;
				$this->menuType = $storedMenu->type;

				foreach ($storedMenu->menuitems as $menuitemRecord) {
					$convertedItem = $this->toMenuitem($menuitemRecord->item);
					if ($convertedItem) {
						$this->items->push($convertedItem);
					}
				}
			}
		}

		if ($this->autoBuild) {
			$this->build();
		}
	}


	public function item(int|string $key, Link|Menu|CustomMenuitem|null $value = null)
	{
		if ($value === null) {
			return $this->items[$key] ?? null;
		}

		$this->items->put($key, $value);
		return $this;
		
	}


	public function items(array|Collection|null $items = null): Collection|MenuBase
	{
		if ($items === null) {
			return $this->items;
		}
		
		if(is_array($items)) {
			$items = collect($items);
		}
		$this->items = $items;
		return $this;
	}


	public function addItem(Link|Menu|CustomMenuitem $item): MenuBase
	{
		$this->items->push($item);
		return $this;
	}

	public function removeItem(int|string $key): MenuBase
	{
		$this->items->pull($key);
		return $this;
	}


	public function autoBuild(?bool $value = null): bool|MenuBase
	{
		if ($value === null) {
			return $this->autoBuild;
		}
		
		$this->autoBuild = $value;
		return $this;
	}


	public function displayTitle(?bool $value = null): bool|MenuBase
	{
		if ($value === null) {
			return $this->displayTitle;
		}
		
		$this->displayTitle = $value;
		return $this;
	}


	protected function toMenuitem(object|array $o): Link|Menu|CustomMenuitem|false
	{
		$menuitem = false;
		$o = (object) $o;
		
		$o->type ??= 'custom';

		if ($o->type == 'link') {
			$o->anchor = isset($o->anchor) ? (array) $o->anchor : [];
			$o->title = isset($o->title) ? (array) $o->title : [];
		}

		$lang = App::getLang();

		switch ($o->type) {
			case 'link':
				$menuitem = new Link([
					'href' => $o->href ?? null,
					'anchor' => $o->anchor[$lang] ?? array_values($o->anchor)[0] ?? '',
					'title' => $o->title[$lang] ?? array_values($o->title)[0] ?? '',
					'target' => $o->target ?? null
				]);
				break;
			case 'menu':
				$menuitem = new Menu([
					'id' => $o->id ?? null,
					'level' => $this->level + 1,
					'displayTitle' => false,
					'autoBuild' => true
				]);
				break;
			case 'custom':
				$menuitem = new CustomMenuitem($o->content);
				break;
			default:
				Debug::alert('Menuitem type: ' . ($o->type ?? '(not set)') . ' not suported.');
				$menuitem = false;
		}

		return $menuitem;
	}


	public function build()
	{
		$this->lv('name', $this->menuName);
		$this->lv('type', $this->menuType);
		$this->lv('level', $this->level);
		$this->lv('displayTitle', $this->displayTitle);
		$this->lv('title', $this->title);
		$this->lv('items', $this->items);

		return $this;
	}


	public function buildOld()
	{
		$lang = App::getLang();

		foreach ($this->items as $item) {
			if ($item['type'] == 'custom') {
				// If the array keys are numeric, it has to be a submenu
				// Otherwise it is a simple menuitem
				if (Misc\array_keys_numeric($item)) {
					$submenuData = [
						'items'=>$item,
						'level'=>$this->level + 1,
						'showTitle'=>false
					];
					
					$this->items[] = new Menu($submenuData);

				} else {
					$target = $item['target'] ?? null;
					$anchorText = '';
					$title = '';

					if (isset($item['anchorText'])) {
						// If there is no anchor text in the currently active language, use the first available one
						if (is_array($item['anchorText'])) {
							$anchorText = $item['anchorText'][$lang] ?? array_values($item['anchorText'])[0];
						}
					}

					if (isset($item['title'])) {
						// If there is no title in the currently active language, use the first available one
						if (is_array($item['title'])) {
							$title = $item['title'][$lang] ?? array_values($item['title'])[0];
						}
					}

					// If the item has a href it is a link, otherwise it is a placeholder
					if (!empty($item['href'])) {
						$linkData = [
							'href' => $item['href'],
							'anchor' => $anchorText,
							'title' => $title,
							'target' => $target
						];

						$this->items[] = new Link($linkData);
					} else {
						$this->items[] = '<span class="menuitem placeholder" title="' . $title . '">' . $anchorText . '</span>';
					}
				}
			} elseif ($item['type'] == 'menu') {
				if (isset($item['id'])) {
					$submenuData['id'] = $item['id'];
				} elseif (isset($item['name'])) {
					$submenuData['menuName'] = $item['name'];
				} else {
					return false;
				}

				$submenuData = [
					'level' => $this->level + 1,
					'showTitle' => false
				];

				$this->items[] = new Menu($submenuData);

			} elseif ($item['type'] == 'link') {
				$target = $item['target'] ?? null;
				$anchorText = '';
				$title = '';

				if (isset($item['id'])) {
					$item['href'] = '@' . $item['id'];
				}

				if (isset($item['anchorText'])) {
					if (is_array($item['anchorText'])) {
						$item['anchorText'] = $item['anchorText'][$lang] ?? array_values($item['anchorText'])[0];
					}
				}

				if (isset($item['title'])) {
					if (is_array($item['title'])) {
						$item['title'] = $item['title'][$lang] ?? array_values($item['title'])[0];
					}
				}

				// If the item has a href it is a link, otherwise it is a placeholder
				if (!empty($item['href'])) {
					$linkData = [
						'href' => $item['href'],
						'anchor' => $anchorText,
						'title' => $title,
						'target' => $target
					];

					$this->items[] = new Link($linkData);

				} else {
					$this->items[] = '<span class="menuitem placeholder" title="' . $item['title'] . '">' . $item['anchorText'] . '</span>';
				}
			} else {
				Debug::alert('Menuitem type: ' . ($item['type'] ?? '(not set)') . ' not suported.');
				$this->items[] = '<span class="menuitem placeholder">' . ($item['anchorText'] ?? 'N/A') . '</span>';
			}
		}

		$this->lv('level', $this->level);
		$this->lv('displayTitle', $this->displayTitle);
		$this->lv('title', $this->title);
		$this->lv('menuItems', $this->items);

		return $this;
	}
}
