<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Settings;

class Robots_TxtBase extends ModuleBase {

	protected static $autoloadModel = false;
	
	protected $dictionary;
	protected $content;

	protected function init()
	{   
		Debug::suppress();
		header('Content-Type: text/plain');
		
		$this->dictionary = [
			'u'=>'user-agent',
			'a'=>'allow',
			'd'=>'disallow'
		];

		$source = Settings::get('robotsTxt');
		$content = '';
		
		foreach ($source as $row) {
			if (!isset($this->dictionary[$row[0]])) {
				$this->error('Item not found in the robots.txt dictionary');
			} else {
				$content .= ($content ? PHP_EOL : '') . $this->dictionary[$row[0]] . ':' . $row[1]; 
			}
		}
		$this->content($content);
	}


	public function dictionary(?array $dictionary = null): array|Robots_TxtBase
	{
		if ($dictionary === null) {
			return $this->dictionary;
		}
		$this->dictionary = $dictionary;
		return $this;
	}


	public function content(?string $content = null): string|Robots_TxtBase
	{
		if ($content === null) {
			return $this->content;
		}
		$this->content = $content;
		return $this;
	}


	public function finalize()
	{
		$this->lv('content', $this->content);
	}
}