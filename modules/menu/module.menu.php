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


	protected function init()
	{
		$this->loadModel();
		
		// Key is the parameter that should be used to preload a menu
		$key = isset($this->params['id']) ? 'id' : (isset($this->params['menuName']) ? 'name' : null);
		
		$this->level($this->params['level'] ?? 0);
		$this->items($this->params['items'] ?? new Collection());
		$this->title($this->params['title'] ?? []);
		$this->displayTitle($this->params['displayTitle'] ?? false);
		
		// Load stored data if requested based on the given key
		if ($key !== null) {
			$storedMenu = $key == 'id'
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


	public function level(?int $level): int|MenuBase
	{
		if ($level === null) {
			return $this->level;
		}

		$this->level = $level;
		return $this;
	}


	public function title(array|string|null $title): array|MenuBase
	{
		if ($title === null) {
			return $this->title;
		}

		if (is_string($title)) {
			$this->title[App::getLang()] = $title;
		} else {
			$this->title = $title;
		}
		
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
					'autoFinalize' => true
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


	public function finalize()
	{
		$this->lv('name', $this->menuName);
		$this->lv('type', $this->menuType);
		$this->lv('level', $this->level);
		$this->lv('displayTitle', $this->displayTitle);
		$this->lv('title', $this->title);
		$this->lv('items', $this->items);

		return $this;
	}
}
