<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\ModuleCore;

class Router_InfoBase extends ModuleCore {

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

		$this->routeInfo = (array) Router::getMatchedRoute();
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
