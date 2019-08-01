<?php

// The system supports registered user forms, and starndard forms
// both must have a handler module, and a handler method defined in this class.

namespace Arembi\Xfw\Core;

class Input_Handler {

	private static $model = null;

	private static $processResult = null;


	public static function processForm($formID)
	{
		self::$model = new Input_HandlerModel();

		$form = self::$model->getFormByID($formID);

		$process['success'] = false; // Only the handler controller should set it to true

		// Message in case an error occurs during the processing of the form
		$process['ihError'] = '';

		if (!$form) {
			$process['ihError'] = 'Form with ID ' . $formID . 'not found.';
			return $process;
		}
		$process['formName'] = $form->formName;

		// Sanitizing posted values
		$formData = [];

		$formDataTypes = ['string', 'int', 'float', 'array', 'file'];

		if (isset($form->formFields)) {
			foreach ($form->formFields as $key => $value) {
				if (!(isset($value['type']) && in_array($value['type'], $formDataTypes))) {
					$process['ihError'] .= 'Wrong type has been set for ' . $form->formFields[$key] . ' in form ' . $process['formName'] . '.';
				} else {
					$formData[$key] = $value['type'];
				}
			}
			if ($process['ihError']) {
				return $process;
			}
		}

		// Checking whether all data is present
		if (count(array_diff_key($formData, Router::$REQUEST)) === 0) {
			// Converting values to expected data types
			// $value holds the data type at this point, but will be
			// replaced by the actual input data
			foreach ($formData as $data => $value) {
				$formData[$data] = Router::$REQUEST[$data];
				settype($formData[$data], $value);
			}
		} else {
			$process['ihError'] = 'Missing parameter for form ' . $process['formName'];
			return $process;
		}

		// Forms can be
		// - global (document module handles them)
		// - module specific (control panel included)
		// If you want to use a form globally, do not assign it to a module,
		// just put the form's handler function in the fh.document.php file

		$controller = $form->moduleName ?? 'document';

		$loaded = App::loadModuleAddon($controller, 'ih');

		if (!$loaded) {
			$process['ihError'] = 'Missing form handler file for ' . $controller . '.';
			return $process;
		}

		$controller = '\\Arembi\\Xfw\\Module\\' . 'IH_' . $controller;

		if (!class_exists($controller)) {
			$process['ihError'] = 'Missing form handler class "' . $controller . ' for form "' . $form->formName . '".';
			return $process;
		} else {
			$controller = new $controller();
			$controller->formData = $formData;

			$handlerMethod = $form->formName;
			if (method_exists($controller, $handlerMethod)) {
				$process['status'] = $controller->$handlerMethod();

				Debug::alert('Form ' . $form->formName . ' processed with function ' . get_class($controller) . '->' . $handlerMethod . '()', 'o');
			} else {
				Debug::alert('Missing form handler function "' . $handlerMethod . '()" for form "' . $form->formName . '".', 'f');
			}
		}
		$process['success'] = true;
		return $process;
	}


	public static function processStandard($controller, $handlerMethod)
	{
		$process['success'] = false;

		if (!$_SESSION['user']->allowedToSendInput()) {
			$process['ihError'] = 'Unauthorized user request.';
			return $process;
		}

		$loaded = App::loadModuleAddon($controller, 'ih');

		if (!$loaded) {
			$process['ihError'] = 'Missing input handler file for ' . $controller . '.';
			return $process;
		}

		$controllerName = '\\Arembi\Xfw\\Module\\' . 'IH_' . $controller;

		if (!class_exists($controllerName)) {
			$process['ihError'] = 'Missing input handler class "' . $controller . ' for form "' . $form['formName'] . '".';
		} else {
			$controller = new $controllerName();

			if (method_exists($controller, $handlerMethod)) {
				$process['status'] = $controller->$handlerMethod();
				Debug::alert('Input processed with function ' . get_class($controller) . '->' . $handlerMethod . '()', 'o');
			} else {
				Debug::alert('Missing input handler function "' . $handlerMethod . '()" for "' . get_class($controller) . '".', 'f');
			}
		}
		$process['success'] = true;
		return $process;
	}


	public static function setProcessResult($data)
	{
		self::$processResult = $data;
	}


	public static function getProcessResult()
	{
		return self::$processResult;
	}

}
