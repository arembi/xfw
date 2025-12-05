<?php
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Settings;
use Illuminate\Support\Collection;

class MenuBase extends ModuleBase {

	protected static $autoloadModel = true;
	
	private $level;
	private $lang;
	private $items;
	private $menuName;
	private $menuType;
	private $title;
	private $displayTitle;


	protected function init()
	{
		$this->level = 0;
		$this->lang = '';
		$this->items = new Collection();
		$this->menuName = '';
		$this->menuType = '';
		$this->title = [];
		$this->displayTitle = true;

		// Key is the parameter that should be used to preload a menu
		$key = isset($this->params['id']) ? 'id' : (isset($this->params['menuName']) ? 'name' : null);
		
		if ($key) {
			$this->invokeModel();
		}

		$this
			->lang($this->params['lang'] ?? App::getLang())
			->level($this->params['level'] ?? 0)
			->title($this->params['title'] ?? [])
			->menuName($this->params['menuName'] ?? '')
			->menuType($this->params['menuType'] ?? '')
			->displayTitle($this->params['displayTitle'] ?? false);

		// Load stored data if requested based on the given key
		if ($key !== null) {
			$storedMenu = $key == 'id'
				? $this->model->getMenuByMenuId($this->params['id'])
				: $this->model->getMenuByMenuName($this->params['menuName']);

			if ($storedMenu) {
				$this->menuName($storedMenu->name);
				$this->menuType($storedMenu->type);

				foreach ($storedMenu->menuitems as $menuitemRecord) {
					$convertedItem = $this->toMenuitem($menuitemRecord->item);
					if ($convertedItem) {
						$this->addItem($convertedItem);
					}
				}
			}
		}

		if (isset($this->params['items'])) {
			$this->items($this->params['items']);
		}
		
	}


	public function finalize(): void
	{
		foreach ($this->items as $item) {
			$item->finalize();
		}
		$this
			->lv('lang', $this->lang)
			->lv('name', $this->menuName)
			->lv('type', $this->menuType)
			->lv('level', $this->level)
			->lv('displayTitle', $this->displayTitle)
			->lv('title', $this->title)
			->lv('items', $this->items);
	}


	public function lang(?string $lang = null): string|MenuBase
	{
		if ($lang === null) {
			return $this->lang;
		}
		if (in_array($lang, array_column(Settings::get('availableLanguages'), 0))) {
			$this->lang = $lang;
		} else {
			$this->error('Cannot set unsupported language.');
		}
		return $this;
	}


	public function menuName(?string $name = null): string|MenuBase
	{
		if ($name === null) {
			return $this->menuName;
		}
		$this->menuName = $name;
		return $this;
	}


	public function menuType(?string $type = null): string|MenuBase
	{
		if ($type === null) {
			return $this->menuType;
		}
		$this->menuType = $type;
		return $this;
	}


	public function item(int|string $key, Link|Menu|Container|null $item = null): Menu|Link|Container|MenuBase
	{
		if ($item === null) {
			return $this->items->get($key);
		}
		$this->addItem($item, $key);
		return $this;
	}


	public function items(array|Collection|null $items = null): Collection|MenuBase
	{
		if ($items === null) {
			return $this->items;
		}

		$this->items = new Collection(); // resetting item collection

		foreach($items as $item) {
			$this->addItem($item);
		}
		return $this;
	}


	public function numberOfItems(): int
	{
		return $this->items->count();
	}


	public function addItem(Link|Menu|Container $item, int|string|null $key = null): MenuBase|false
	{
		if ($item->error()['errorOccured']) {
			return false;
		}
		if ($item->moduleName() == 'menu') {
			$item->level($this->level + 1);
		}
		if ($key) {
			$this->items->put($key, $item);
		} else {
			$this->items->push($item);
		}
		return $this;
	}


	public function removeItem(int|string $key): MenuBase
	{
		$this->items->forget($key);
		return $this;
	}


	public function level(?int $level = null): int|MenuBase
	{
		if ($level === null) {
			return $this->level;
		}
		$this->level = $level;
		return $this;
	}


	public function title(array|string|null $title = null): array|MenuBase
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


	protected function toMenuitem(object|array $o): Link|MenuBase|Container|null
	{
		$o = (object) $o;
		$menuItem = null;
		
		$o->type ??= 'custom';

		if ($o->type == 'link') {
			$o->anchor = isset($o->anchor) ? (array) $o->anchor : [];
			$o->title = isset($o->title) ? (array) $o->title : [];
		}

		$lang = App::getLang();

		switch ($o->type) {
			case 'link':
				$menuItem = new Link([
					'href' => $o->href ?? null,
					'anchor' => $o->anchor[$lang] ?? array_values($o->anchor)[0] ?? '',
					'title' => $o->title[$lang] ?? array_values($o->title)[0] ?? '',
					'target' => $o->target ?? null,
					'autoFinalize'=>true
				]);
				break;
			case 'menu':
				$menuItem = new Menu([
					'id' => $o->id ?? null,
					'level' => $this->level + 1,
					'displayTitle' => false,
					'autoFinalize' => true
				]);
				break;
			case 'custom':
				$menuItem = new Container([
					'content'=>$o->content,
					'displayTitle'=>false
				]);
				break;
			default:
				Debug::alert('Menuitem type: ' . ($o->type ?? '(not set)') . ' not suported.');
				$menuItem = false;
		}

		return $menuItem;
	}
}
