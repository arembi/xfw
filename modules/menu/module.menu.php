<?php
namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\App;
use Arembi\Xfw\Core\Debug;
use Arembi\Xfw\Core\Misc;


class MenuBase extends \Arembi\Xfw\Core\ModuleCore {

	protected function main(&$options)
	{
		if (!isset($options['showTitle'])) {
			$options['showTitle'] = false;
		}

		if (isset($options['ID'])) {
			$this->loadModel();
			$menu = $this->model->getMenuByMenuID($options['ID']);
		} elseif (isset($options['menuName'])) {
			$this->loadModel();
			$menu = $this->model->getMenuByMenuName($options['menuName']);
		} else {
			$menu = new \stdClass();
			$menu->name = '';
			$menu->menuitems = [];
		}

		$title = $options['title'] ?? $menu->name;

		// Merge custom menu items with the stored onces
		if (isset($options['items']) && is_array($options['items'])) {
			array_walk($options['items'], function (&$item) {
				$item['type'] = 'custom';
			});
			$menu->menuitems = array_merge($menu->menuitems, $options['items']);
		}

		// In case there are no items in this menu, we just return false
		if (empty($menu->menuitems)) {
			return false;
		}

		// Level keeps track of the depth of the menu
		if (empty($options['level'])) {
			$options['level'] = 0;
		}

		$lang = App::getLang();

		foreach ($menu->menuitems as $item) {
			if ($item['type'] == 'custom') {
				// If the array keys are numeric, it has to be a $submenu
				// Otherwise it is a simple menuitem
				if (Misc\array_keys_numeric($item)) {
					$submenuData = [
						'items' => $item,
						'level' => $options['level'] + 1,
						'showName' => false
					];
					$submenu = new Menu($submenuData);
					$menuItems[] = $submenu->processLayout()->getLayoutHTML();
				} else {
					// Filling up empty values
					if (!isset($item['target'])) {
						$item['target'] = null;
					}

					if (isset($item['anchorText'])) {
						if (is_array($item['anchorText'])) {
							$item['anchorText'] = $item['anchorText'][$lang]
								?? array_values($item['anchorText'])[0];
						}
					} else {
						$item['anchorText'] = '';
					}

					if (isset($item['title'])) {
						if (is_array($item['title'])) {
							$item['title'] = $item['title'][$lang]
								?? array_values($item['title'])[0];
						}
					} else {
						$item['title'] = '';
					}

					// If the item has a href it is a link, otherwise it is a placeholder
					if (!empty($item['href'])) {
						$linkData = [
							'href' => $item['href'],
							'anchor' => $item['anchorText'],
							'title' => $item['title'],
							'target' => $item['target']
						];
						
						$link = new Link($linkData);

						$menuItems[] = $link->processLayout()->getLayoutHTML();
					} else {
						$menuItems[] = '<span class="menuitem placeholder" title="' . $item['title'] . '">' . $item['anchorText'] . '</span>';
					}
				}
			} elseif ($item['type'] == 'menu') {
				$submenuData = [
					'level' => $options['level'] + 1,
					'showName' => false
				];

				if (isset($item['id'])) {
					$submenuData['ID'] = $item['id'];
				} elseif (isset($item['name'])) {
					$submenuData['menuName'] = $item['name'];
				} else {
					return false;
				}

				$submenu = new Menu($submenuData);
				$menuItems[] = $submenu->processLayout()->getLayoutHTML();
			} elseif ($item['type'] == 'link') {
				if (isset($item['id'])) {
					$item['href'] = '@' . $item['id'];
				}

				// Filling up empty values
				if (!isset($item['target'])) {
					$item['target'] = null;
				}

				if (isset($item['anchorText'])) {
					if (is_array($item['anchorText'])) {
						$item['anchorText'] = $item['anchorText'][$lang]
							?? array_values($item['anchorText'])[0];
					}
				} else {
					$item['anchorText'] = '';
				}

				if (isset($item['title'])) {
					if (is_array($item['title'])) {
						$item['title'] = $item['title'][$lang]
							?? array_values($item['title'])[0];
					}
				} else {
					$item['title'] = '';
				}

				// If the item has a href it is a link, otherwise it is a placeholder
				if (!empty($item['href'])) {
					$linkData = [
						'href' => $item['href'],
						'anchor' => $item['anchorText'],
						'title' => $item['title'],
						'target' => $item['target']
					];

					$link = new Link($linkData);

					$menuItems[] = $link->processLayout()->getLayoutHTML();
				} else {
					$menuItems[] = '<span class="menuitem placeholder" title="' . $item['title'] . '">' . $item['anchorText'] . '</span>';
				}
			} else {
				Debug::alert('Menuitem type: ' . ($item['type'] ?? '(not set)') . ' not suported.');
				$menuItems[] = '<span class="menuitem placeholder">' . ($item['anchorText'] ?? 'N/A') . '</span>';
			}
		}

		$this->lv('level', $options['level']);
		$this->lv('showTitle', $options['showTitle']);
		$this->lv('title', $title);
		$this->lv('menuItems', $menuItems);
	}
}
