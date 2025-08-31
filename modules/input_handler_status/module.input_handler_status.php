<?php

namespace Arembi\Xfw\Module;

use Arembi\Xfw\Core\Input_Handler;
use Arembi\Xfw\Core\Router;
use Arembi\Xfw\Core\ModuleCore;

class Input_Handler_StatusBase extends ModuleCore {

	protected $status;
	protected $message;
	protected $dataType;
	protected $vocabulary;


	protected function init()
	{
		$this->status = 'null';
		$this->message = 'null';
		$this->dataType = 'null';
		$this->vocabulary = [
			Input_Handler::DATA_NOT_PROCESSED => 'not processed',
			Input_Handler::RESULT_ERROR => 'error',
			Input_Handler::RESULT_WARNING => 'warning',
			Input_Handler::RESULT_SUCCESS => 'success'
		];
		$ihResult = Router::inputHandlerResult();
		
		$this->loadModel();
		$this->loadPathParams();
		
		
		if ($ihResult) {
			$this->status = $this->vocabulary[$ihResult->status()];
			$this->message = $ihResult->message();
			$this->dataType = gettype($ihResult->data());
		}

		$this->lv('status', $this->status);
		$this->lv('message', $this->message);
		$this->lv('dataType', $this->dataType);
	}

}
