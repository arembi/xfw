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


	public static function processStoredForm($formId)
	{
		self::$model = new Input_HandlerModel();
		$result = new Input_Handler_Result();

		if (Config::get('csrfRequired') && $_SERVER['REQUEST_METHOD'] !== 'GET') {
			if (!Session::validateCsrfToken(Router::request('_csrf'))) {
				$result
					->status(self::RESULT_ERROR)
					->message('Invalid CSRF token.');
				return $result;
			}
		}
		if (!$_SESSION['user']->isAllowedToSendInput()) {
			$result
				->status(self::RESULT_ERROR)
				->message('Unauthorized user request.');
			return $result;
		}

		$formData = [];
		$formName = '';
		$moduleAddonLoaded = false;
		$controllerClass = '';
		$handlerModule = '';
		$controller = null;
		$handlerMethod = '';
		$form = self::$model->getFormById($formId);

		if (!$form) {
			$result->status(self::RESULT_ERROR)
				->message('Form with id ' . $formId . 'not found.');
			return $result;
		}

		// Sanitizing posted values
		if (!empty($form->fields)) {
			foreach ($form->fields as $key => $value) {
				if (!(isset($value['type']) && in_array($value['type'], self::$formDataTypes))) {
					$result
						->status(self::RESULT_ERROR)
						->message('Wrong type has been set for ' . $form->fields[$key] . ' in form ' . $form->name . '.');
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
				->message('Missing parameter(s) for form ' . $form->name);
			return $result;
		}

		// Forms can be
		// - global (document module handles them)
		// - module specific (control panel included)
		// If you want to use a form globally, do not assign it to a module,
		// just put the form's handler function in the ih.document.php file

		$handlerModule = $form->module->name ?? 'document';
		$handlerMethod = $form->name;
		$result
			->handlerModule($handlerModule)
			->handlerMethod($handlerMethod);

		if (!self::isHandlerAllowed($handlerModule, $handlerMethod)) {
			$result
				->status(self::RESULT_ERROR)
				->message('Handler is not allowed.');
			return $result;
		}

		$moduleAddonLoaded = App::loadModuleAddon($handlerModule, 'ih');

		if (!$moduleAddonLoaded) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing form handler addon for ' . $handlerModule . '.');
			return $result;
		}

		$controllerClass = '\\Arembi\\Xfw\\Module\\' . 'IH_' . $handlerModule;

		if (!class_exists($controllerClass)) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing form handler class "' . $controllerClass . ' for form "' . $form->name . '".');
			return $result;
		} else {
			$controller = new $controllerClass();

			// Passing the sent data to the controller module
			$controller->setFormData($formData);

			if (method_exists($controller, $handlerMethod)) {
				$controller->$handlerMethod($result);
				Debug::alert('Form ' . $form->name . ' processed with method ' . get_class($controller) . '->' . $handlerMethod . '()', 'o');
			} else {
				Debug::alert('Missing form handler method "' . $handlerMethod . '()" for form "' . $form->name . '".', 'f');
			}
		}
		return $result;
	}


	public static function processGenericRequest(string $handlerModule, string $handlerMethod)
	{
		$result = new Input_Handler_Result();
		$moduleAddonLoaded = false;
		$controllerClass = '';
		$controller = null;

		if (Config::get('csrfRequired') && $_SERVER['REQUEST_METHOD'] !== 'GET') {
			if (!Session::validateCsrfToken(Router::request('_csrf'))) {
				$result
					->status(self::RESULT_ERROR)
					->message('Invalid CSRF token.');
				return $result;
			}
		}

		$result
			->handlerModule($handlerModule)
			->handlerMethod($handlerMethod);
		
		if (!$_SESSION['user']->isAllowedToSendInput()) {
			$result
				->status(self::RESULT_ERROR)
				->message('Unauthorized user request.');
			return $result;
		}

		if (!self::isHandlerAllowed($handlerModule, $handlerMethod)) {
			$result
				->status(self::RESULT_ERROR)
				->message('Handler is not allowed.');
			return $result;
		}

		$moduleAddonLoaded = App::loadModuleAddon($handlerModule, 'ih');

		if (!$moduleAddonLoaded) {
			$result
				->status(self::RESULT_ERROR)
				->message('Missing input handler file for ' . $handlerModule . '.');
			return $result;
		}

		$controllerClass = '\\Arembi\Xfw\\Module\\' . 'IH_' . $handlerModule;

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
		$result->success = false;
		$result->destination = '';
		$result->message = '';
		$result->ext = '';

		$sysFs = FS::getFilesystem('sys');
		$sentFile = Router::files($uploadInput);
		$acceptedMimes = $acceptedMimes ?: Config::get('uploadAcceptedMimeTypes');
		$uploadMime = '';
		$uploadExtension = '';
		$mimeUnderscorePos = false;
		$firstValidExtension = '';
	
		try {
			if (empty($acceptedMimes)) {
				throw new RuntimeException('No accepted MIME types configured.');
			}
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

			try {
				$tempFilePath = $sentFile['tmp_name'] . '_' . $sentFile['name'];
				$sysFs->move($sentFile['tmp_name'], $tempFilePath, [
					'visibility'=>'private',
					'directory_visibility'=>'private'
				]);
				$uploadMime = $sysFs->mimeType($tempFilePath);
			} catch (FilesystemException | UnableToRetrieveMetadata $exception) {
				Debug::alert($exception->getMessage(), 'w');
				$uploadMime = 'text/plain';
			}
			
			$uploadExtension = pathinfo($tempFilePath, PATHINFO_EXTENSION);
			
			$validMime = array_filter($acceptedMimes, function ($value, $key) use ($uploadMime, $uploadExtension) {
				return $value == $uploadMime && mb_strpos($key, $uploadExtension) !== false;
			}, ARRAY_FILTER_USE_BOTH);
			
			if (empty($validMime)) {
				throw new RuntimeException('Invalid file format.');
			}

			$firstValidExtension = array_key_first($validMime);

			// Removing enumeration from the extension marker
			$mimeUnderscorePos = strpos($firstValidExtension, "_");
			if ($mimeUnderscorePos !== false) {
				$result->ext = substr($firstValidExtension, 0, $mimeUnderscorePos);
			} else {
				$result->ext = $firstValidExtension;
			}
			
			$targetPath = UPLOADS_DIR . DS . $targetDir;
			if (!is_dir($targetPath)) {
				$sysFs->createDirectory($targetPath, [
					'visibility' => 'private',
					'directory_visibility' => 'private'
				]);
			}
			$result->destination = $targetPath . DS . sprintf('%s.%s', sha1_file($tempFilePath), $result->ext);
			
			try {
				$sysFs->move($tempFilePath, $result->destination, [
					'visibility'=>'private',
					'directory_visibility'=>'private'
				]);
				$result->success = true;
			} catch (FilesystemException | UnableToMoveFile $exception) {
				$result->message = $exception->getMessage();
			}

		} catch (RuntimeException $e) {
			$result->message = $e->getMessage();
		}

		return $result;
	}

	private static function isHandlerAllowed(string $handlerModule, string $handlerMethod): bool
	{
		$allowlist = Settings::get('inputHandlerAllowlist');

		if ($allowlist === null) {
			return true;
		}

		if (!is_array($allowlist) || empty($allowlist)) {
			return false;
		}

		if (isset($allowlist['*']) && (in_array('*', $allowlist['*'], true) || in_array($handlerMethod, $allowlist['*'], true))) {
			return true;
		}

		if (!isset($allowlist[$handlerModule])) {
			return false;
		}

		return in_array('*', $allowlist[$handlerModule], true)
			|| in_array($handlerMethod, $allowlist[$handlerModule], true);
	}

}
