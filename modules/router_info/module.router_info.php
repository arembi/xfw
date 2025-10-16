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
		$this->loadPathParams();
		
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

		$this->routeInfo = (array) Router::getMatchedRoute() ?: ['id'=>0, 'moduleName'=>'404'];
		$this->routeInfo['action'] = Router::getRequestedAction();

		$ihResult = Router::inputHandlerResult();
		
		if ($ihResult) {
			$this->inputHandlerInfo['status'] = $this->inputHandlerInfo['vocabulary'][$ihResult->status()];
			$this->inputHandlerInfo['message'] = $ihResult->message();
			$this->inputHandlerInfo['dataType'] = gettype($ihResult->data());
		}
		$this->lv('input', $this->inputHandlerInfo);
		$this->lv('route', $this->routeInfo);
	}

}
