<?php

namespace Arembi\Xfw\Inc;

use Arembi\Xfw\Core\Router;

class Js {
	
	private $type;
	private $content;
	private $async;

	
	public function __construct(string $type, string $content, bool $async = false)
	{
		$this->type($type);
		$this->content($content);
		$this->async($async);
	}


	public function type(?string $type = null): string|Js|false
	{
		if ($type === null) {
			return $this->type;
		}

		if (in_array($type, ['src', 'inline'])) {
			$this->type = $type;
			return $this;
		}

		return false;
	
	}


	public function content(?string $content = null): string|Js
	{
		if ($content === null) {
			return $this->content;
		}

		if ($this->type() == 'src') {
			$content = Router::url($content);
		}

		$this->content = $content;
		return $this;
	}


	public function async(?bool $async = null): bool|Js
	{
		if ($async === null) {
			return $this->async;
		}
		$this->async = $async;
		return $this;
	}
}