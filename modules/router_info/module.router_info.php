<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\ModuleBase;
use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;

class Router_InfoBase extends ModuleBase {

	protected $inputHandlerInfo;
	protected $routeInfo;

	protected function init()
	{
		$this->inputHandlerInfo = [
			'inputInfo'=>Router::inputInfo(),
			'status'=>'null',
			'message'=>'null',
			'dataType'=>'null',
			'vocabulary'=>[
				Input_Handler::DATA_NOT_PROCESSED=>'not processed',
				Input_Handler::RESULT_ERROR=>'error',
				Input_Handler::RESULT_WARNING=>'warning',
				Input_Handler::RESULT_SUCCESS=>'success'
			]
		];
		$this->routeInfo = (array) Router::getMatchedRoute();
		$this->routeInfo['id'] ??= 'N/A';
		$this->routeInfo['action'] = Router::getRequestedAction();

		$ihResult = Router::inputHandlerResult();
		
		if ($ihResult) {
			$this->inputHandlerInfo['status'] = $this->inputHandlerInfo['vocabulary'][$ihResult->status()];
			$this->inputHandlerInfo['message'] = $ihResult->message();
			$this->inputHandlerInfo['dataType'] = gettype($ihResult->data());
		}
	}


	public function finalize()
	{
		$this
			->lv('input', $this->inputHandlerInfo)
			->lv('route', $this->routeInfo);
	}


	public function routeInfo(?array $info): array|Router_InfoBase
	{
		if ($info === null) {
			return $this->routeInfo;
		}
		$this->routeInfo = $info;
		return $this;
	}


	public function inputHandlerInfo(?array $info): array|Router_InfoBase
	{
		if ($info === null) {
			return $this->inputHandlerInfo;
		}
		$this->inputHandlerInfo = $info;
		return $this;
	}
}
