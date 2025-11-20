<?php

namespace Arembi\Xfw\Inc;

use Arembi\Xfw\Core\Router;

class Js {
	
	private $type;
	private $src;
	private $content;
	private $async;
	private $defer;
	private $crossorigin;
	private $integrity;
	private $nomodule;
	private $referrerpolicy;

	
	public function __construct(
		string $src = '',
		string $type = '',
		bool $async = false,
		bool $defer = false,
		string $crossorigin = '',
		string $integrity = '',
		string $nomodule = '',
		string $referrerpolicy = '',
		string $content = ''
	)		
	{
		$this->src($src);
		$this->type($type);
		$this->async($async);
		$this->defer($defer);
		$this->crossorigin($crossorigin);
		$this->integrity($integrity);
		$this->nomodule($nomodule);
		$this->referrerpolicy($referrerpolicy);
		$this->content($content);
	}


	public function type(?string $type = null): string|Js
	{
		if ($type === null) {
			return $this->type;
		}
		$this->type = $type;
		return $this;
	}


	public function src(?string $src = null): string|Js
	{
		if ($src === null) {
			return $this->src;
		}

		$this->src = Router::url($src);
		return $this;
	}


	public function content(?string $content = null): string|Js
	{
		if ($content === null) {
			return $this->content;
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


	public function defer(?bool $defer = null): bool|Js
	{
		if ($defer === null) {
			return $this->defer;
		}
		$this->defer = $defer;
		return $this;
	}


	public function crossorigin(?string $crossorigin = null): string|Js
	{
		if ($crossorigin === null) {
			return $this->crossorigin;
		}
		if (!in_array($crossorigin, [
			'',
			'anonymous',
			'use-credentials'
		])) {
			$crossorigin = '';
		}
		$this->crossorigin = $crossorigin;
		return $this;
	}


	public function integrity(?string $integrity = null): string|Js
	{
		if ($integrity === null) {
			return $this->integrity;
		}
		$this->integrity = $integrity;
		return $this;
	}


	public function nomodule(?bool $nomodule = null): bool|Js
	{
		if ($nomodule === null) {
			return $this->nomodule;
		}
		$this->nomodule = $nomodule;
		return $this;
	}


	public function referrerpolicy(?string $referrerpolicy = null): string|Js
	{
		if ($referrerpolicy === null) {
			return $this->referrerpolicy;
		}
		if (!in_array($referrerpolicy, [
			'',
			'no-referrer',
			'no-referrer-when-downgrade',
			'origin',
			'origin-when-cross-origin',
			'same-origin',
			'strict-origin',
			'strict-origin-when-cross-origin',
			'unsafe-url'
		])) {
			$referrerpolicy = '';	
		}
		$this->referrerpolicy = $referrerpolicy;
		return $this;
	}
}