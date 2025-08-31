<?php

// The system supports registered user forms, and starndard forms
// both must have a handler module, and a handler method defined in this class.

namespace Arembi\Xfw\Core;

use stdClass;
use RuntimeException;
use League\Flysystem\FilesystemException;
use League\Flysystem\UnableToMoveFile;

class Input_Handler {

	private static $model;
	private static $formDataTypes;

	public const DATA_NOT_PROCESSED = 0;
	public const RESULT_SUCCESS = 1;
	public const RESULT_WARNING = 2;
	public const RESULT_ERROR = 3;


	public static function init()
	{
		self::$formDataTypes = [
			'string',
			'int',
			'float',
			'array',
			'file'
		];
		self::$model = null;
	}


	public static function processForm($formId)
	{
		$process = [
			'success'=>false, // Only the handler controller should set it to true
			'ihError'=>'', // Message in case an error occurs during the processing of the form
			'formName'=>'',
			'status'=>[
				self::RESULT_SUCCESS,
				'not processed'
			]
		];
		
		self::$model = new Input_HandlerModel();
		$result = new Input_Handler_Result();

		$formData = [];
		$formName = '';
		$form = self::$model->getFormById($formId);
		$moduleAddonLoaded = false;
		$controllerClass = '';
		$controllerModule = '';
		$controller = null;
		$handlerMethod = '';

		if (!$form) {
			//$process['ihError'] = 'Form with id ' . $formId . 'not found.';
			$result->status(self::RESULT_ERROR)
				->message('Form with id ' . $formId . 'not found.');
			return $result;
		}

		$formName = $form->formName;
		//$process['formName'] = $form->formName;

		// Sanitizing posted values

		if (isset($form->formFields)) {
			foreach ($form->formFields as $key => $value) {
				if (!(isset($value['type']) && in_array($value['type'], self::$formDataTypes))) {
					//$process['ihError'] .= 'Wrong type has been set for ' . $form->formFields[$key] . ' in form ' . $process['formName'] . '.';
					$result
						->status(self::RESULT_ERROR)
						->message('Wrong type has been set for ' . $form->formFields[$key] . ' in form ' . $formName . '.');
				} else {
					$formData[$key] = $value['type'];
				}
			}
			if ($result->status() === self::RESULT_ERROR) {
				return $result;
			}
		}

		// Checking whether all data is present
		if (count(array_diff_key($formData, Router::request())) === 0) {
			// Converting values to expected data types
			// $value holds the data type at this point, but will be
			// replaced by the actual input data
			foreach ($formData as $data => $value) {
				$formData[$data] = Router::request($data);
				settype($formData[$data], $value);
			}
		} else {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing parameter(s) for form ' . $formName);
			return $result;
		}

		// Forms can be
		// - global (document module handles them)
		// - module specific (control panel included)
		// If you want to use a form globally, do not assign it to a module,
		// just put the form's handler function in the ih.document.php file

		$controllerModule = $form->moduleName ?? 'document';

		$moduleAddonLoaded = App::loadModuleAddon($controllerModule, 'ih');

		if (!$moduleAddonLoaded) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing form handler addon for ' . $controllerModule . '.');
			return $result;
		}

		$controllerClass = '\\Arembi\\Xfw\\Module\\' . 'IH_' . $controllerModule;

		if (!class_exists($controllerClass)) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing form handler class "' . $controllerClass . ' for form "' . $formName . '".');
			return $result;
		} else {
			$controller = new $controllerClass();

			// Passing the sent data to the controller module
			$controller->setFormData($formData);

			$handlerMethod = $formName;
			if (method_exists($controller, $handlerMethod)) {
				$controller->$handlerMethod($result);
				Debug::alert('Form ' . $formName . ' processed with method ' . get_class($controller) . '->' . $handlerMethod . '()', 'o');
			} else {
				Debug::alert('Missing form handler method "' . $handlerMethod . '()" for form "' . $formName . '".', 'f');
			}
		}
		return $result;
	}


	public static function processStandard(string $controllerModule, string $handlerMethod)
	{
		$result = new Input_Handler_Result();
		$moduleAddonLoaded = false;
		$controllerClass = '';
		$controller = null;
		
		if (!$_SESSION['user']->allowedToSendInput()) {
			$result
				->status(self::RESULT_ERROR)
				->message('Unauthorized user request.');
			return $result;
		}

		$moduleAddonLoaded = App::loadModuleAddon($controllerModule, 'ih');

		if (!$moduleAddonLoaded) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing input handler file for ' . $controllerModule . '.');
			return $result;
		}

		$controllerClass = '\\Arembi\Xfw\\Module\\' . 'IH_' . $controllerModule;

		if (!class_exists($controllerClass)) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing input handler class "' . $controllerClass . '".');
		} else {
			$controller = new $controllerClass();
			
			if (method_exists($controller, $handlerMethod)) {
				$controller->$handlerMethod($result);
				Debug::alert('Input processed with method ' . get_class($controller) . '->' . $handlerMethod . '()', 'o');
			} else {
				Debug::alert('Missing input handler method "' . $handlerMethod . '()" for "' . get_class($controller) . '".', 'f');
			}
		}
		return $result;
	}


	// $mime has to be an array with extension as key and mime as value, f.i. ["png" => "image/png"]
	public static function uploadFile(string $uploadInput, array $acceptedMimes = [], string $targetDir = 'temp', )
	{
		$result = new stdClass();
		$result->ok = false;
		$result->destination = '';
		$result->message = '';
		$result->ext = '';

		$sentFile = Router::files($uploadInput);
		$sysFs = FS::getFilesystem('sys');

		try {
			// Undefined | Multiple Files | Corruption Attack
			// If this request falls under any of them, treat it invalid.
			if (
				!isset($sentFile['error']) ||
				is_array($sentFile['error'])
			) {
				throw new RuntimeException('Invalid parameters.');
			}

			switch ($sentFile['error']) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					throw new RuntimeException('No file sent.');
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					throw new RuntimeException('Exceeded filesize limit.');
				default:
					throw new RuntimeException('Unknown errors.');
			}

			if ($sentFile['size'] > Config::get('uploadMaxFileSize')) {
				throw new RuntimeException('Exceeded filesize limit.');
			}
			
			$uploadMime = $sysFs->mimeType($sentFile['tmp_name']);
			
			$acceptedMimes = $acceptedMimes ?? Config::get('uploadAllowedMimeTypes');

			$result->ext = array_search($uploadMime, $acceptedMimes, true);

			if (false === $result->ext) {
				throw new RuntimeException('Invalid file format.');
			}

			// Removing enumeration from the extension marker
			$underscorePos = strpos($result->ext, "_");
			if ($underscorePos !== false) {
				$result->ext = substr($result->ext, 0, $underscorePos);
			}
			
			$result->destination = UPLOADS_DIR . DS . $targetDir . DS . sprintf('%s.%s', sha1_file($sentFile['tmp_name']), $result->ext);

			try {
				$sysFs->move($sentFile['tmp_name'], $result->destination, [
					'visibility'=>'public',
					'directory_visibility'=>'public'
				]);
			} catch (FilesystemException | UnableToMoveFile $exception) {
				$result->message = $exception->getMessage();
			}

			$result->ok = true;

		} catch (\RuntimeException $e) {
			$result->message = $e->getMessage();
		}

		return $result;
	}

}
