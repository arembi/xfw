<?php
/*
*	Loads core classes
*	Invokes the first module
*	Starts the processing of the layout
*/

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Module\Document;

abstract class App {

	// The App's model
	private static $model = null;

	// The list of core modules, ONLY THESE WILL BE LOADED to the core
	private static $coreModules = [];

	// Modules that have been installed to the current domain
	private static $installedModules = [];

	// Active modules are the successfully installed and loaded modules
	private static $activeModules = [];

	// $registeredModules keeps track of which modules have already been loaded
	// by the system, it holds the references to all embedded modules
	private static $registeredModules = [];

	// Autoincrementing inner identifier for embedded modules
	private static $registeredModuleID;

	// Path parameter translation rules for modules
	private static $pathParamOrders = [];

	/*
	 * Whenever the system loads a layout blueprint from a file, it keeps it here for possible reusage,
	 * so no further file operations will be required
	 * */
	private static $layoutBlueprints = [];

	// Language
	private static $lang = '';

	// AMP enabled
	private static $AMP = 'off';


	public static function init()
	{
		self::$coreModules = [
			'model',
			'settings',
			'misc',
			'language',
			'session',
			'module',
			'input_handler',
			'router',
			'user',
			'amp'
		];

		// Loading configuration
		self::loadConfigAndDebug();

		// Loading scripts from the engine include directory
		self::loadScriptsFromDirectory(ENGINE . DS . 'include') ;

		// Load libraries from other vendors
		self::loadThirdPartyLibs();

		// Load core controllers and models
		self::loadCore();

		Debug::init();
		Debug::alert('[SYS] Configuration loaded');
		Debug::alert('[SYS] Core loaded');

		// Do not show PHP errors in production environment
		if (!Config::_('debugMode')) {
			error_reporting(0);
			define('APP_ENV', 'prod');
		} else {
			define('APP_ENV', 'dev');
		}

		// Starting a timer to measure page load speed
		$pageloadTimer = new Misc\Timer();
		$pageloadTimer->mark();

		// Apply charset settings
		mb_language(Config::_('mbLanguage'));
		mb_internal_encoding(Config::_('mbInternalEncoding'));

		// Establishing database connection and interface
		new Database('sys');

		// Initializing the language module
		Language::init();

		// Determine environment (protocol, domain)
		Router::init();
		Router::getEnvironment();
		Debug::alert('[SYS] Router initialized');

		// Loading scripts from the engine include directory
		self::loadScriptsFromDirectory(DOMAIN_DIRECTORY . DS . 'include') ;

		// Initializing sessions
		Session::init();
		Debug::alert('[SYS] Session started');

		// Setting the model of the App class
		self::$model = new AppModel();

		// If the user has not logged in before, load default values
		if (empty($_SESSION['user'])) {
			$_SESSION['user'] = new User('_guest');
		}

		// Loading Settings (settings stored in the database)
		Settings::init();
		Debug::alert('[SYS] Settings loaded');

		// Load the modules with ModuleCore
		self::loadInstalledModules();

		Debug::alert('[SYS] Modules loaded');

		// Load routes, links, redirects
		Router::loadData();

		/*
		 * The system requires the document layout and a primary module from the router
		 * For the primary module, the action is also required
		 * */
		$matchedRoute = Router::parseRoute();

		Debug::alert('[SYS] URI parsed');

		// Initializing inner autoincrementing ID
		self::$registeredModuleID = 0;

		/*
		The module options for the document have to be set manually
		note: the layout may be overridden by AMP
		*/
		$documentModuleOptions = [
			'layout' => $matchedRoute['documentLayout'],
			'primaryModule' => $matchedRoute['primary'],
			'parentModule' => null
			];

		// The response is simply an instance of a document module
		$response = new Document($documentModuleOptions);

		Debug::alert('[SYS] Document ready to render');

		// Start the layout loop and show the result
		$response->processLayout();

		// If AMP is enabled the output needs to be cleaned to meet the AMP
		// standards
		if (self::AMP() == 'on') {
			$response->HTMLToAMP(true);
		}

		$response->render();

		// Final time marker
		$pageloadTimer->mark();
		Debug::alert('Page load time: ' . $pageloadTimer->getFullTime() . 's.', 'i');

		// Memory usage information
		Debug::alert('Memory usage: ' . number_format(memory_get_usage() / (1024 * 1024), 4) . ' MB', 'i');
		Debug::alert('Memory peak usage: ' . number_format(memory_get_peak_usage() / (1024 * 1024), 4) . ' MB', 'i');

		// Show debug messages
		Debug::render();
	}


	private static function loadConfigAndDebug()
	{
		$configFile = CORE . DS .'config.php';
		if (file_exists($configFile)) {
			require($configFile);
			Config::init();
		} else {
			die ('Inappropriate configuration, please contact the adminisrator');
		}
		$debugFile = CORE . DS .'debug.php';
		if (file_exists($debugFile)) {
			require($debugFile);
		} else {
			die ('An error occured during initialization.');
		}
	}


	private static function loadCore()
	{
		// The base models
		$files = glob(ENGINE . DS . 'models' . DS . '*.php');
		foreach ($files as $file) {
			include($file);
		}

		// The core module files
		foreach (self::$coreModules as $module) {
			// The controller
			$controllerFileName = $module . '.php';
			$controllerFile = CORE . DS . $controllerFileName;

			if (file_exists($controllerFile)) {
				include($controllerFile);
				Debug::alert('Core controller %' .  $module . ' successfully loaded.', 'o');
			} else {
				Debug::alert('Core controller %' .  $module . ' could not be loaded.', 'f');
			}

			// The model
			$modelFileName = 'model.' . $module . '.php';
			$modelFile  = CORE . DS . $modelFileName;

			if (file_exists($modelFile)) {
				require($modelFile);
				Debug::alert('Model for core module %' .  $module . ' successfully loaded.', 'o');
			} else {
				Debug::alert('Model for core module %' .  $module . ' could not be loaded.', 'w');
			}
		}

		// Additionally we have to load the model of appCore here, because it extends the previously loaded ModelCore class
		$modelFileName = 'model.app.php';

		if (file_exists(CORE . DS . $modelFileName)) {
			require(CORE . DS . $modelFileName);
			Debug::alert('Model for core module %app successfully loaded.', 'o');
		} else {
			Debug::alert('Model for core module %app could not be loaded.', 'w');
		}
	}


	private static function loadScriptsFromDirectory(string $directory)
	{
		$fileNamePattern = $directory . DS . '*.php';

		$files = glob($fileNamePattern);

		foreach ($files as $key => $file) {
			$iPos = strrpos($file, 'interface.');
			if ($iPos !== false && strrpos($file, '/') < $iPos) {
				include($file);
				unset($files[$key]);
			}
		}

		foreach ($files as $file) {
			include($file);
		}
	}


	private static function loadInstalledModules()
	{
		// Get data from the database
		self::loadInstalledModuleData();

		/*
		 * Module behaviour can be modified distinctly for every domain
		 * If there is a proper file in the SITES/[domain]/modules directory, that
		 * class can extend the basic module
		 * */
		foreach (self::$installedModules as $currentModule) {
			$moduleName = $currentModule->name;

			if ($currentModule->active || $currentModule->category == 'core') {
				$moduleFilePath = 'modules' . DS . $moduleName . DS . 'module.' . $moduleName . '.php';

				$loaded = false;

				if (file_exists(ENGINE . DS . $moduleFilePath)) {
					include(ENGINE . DS . $moduleFilePath);
					$loaded = true;
					Debug::alert('Base module %' . $moduleName . ' successfully loaded.', 'o');
				} else {
					Debug::alert('Base module %' . $moduleName . ' could not be loaded.', 'n');
				}

				// Looking for overrides
				if (file_exists(SITES . DS . DOMAIN . DS . $moduleFilePath)) {
					include(SITES . DS . DOMAIN . DS . $moduleFilePath);
					Debug::alert('Override for module %' . $moduleName . ' successfully loaded and activated.', 'o');
					$loaded = true;
				} elseif ($loaded) {
					// Creating an alias for ModuleNameBase to ModuleName,
					// because the system uses only ModuleName classes
					class_alias('\\Arembi\Xfw\\Module\\' . $moduleName . 'Base', '\\Arembi\Xfw\\Module\\' . $moduleName);

					Debug::alert('Module %' . $moduleName . ' successfully loaded and activated.', 'o');
				}

				if (!$loaded) {
					Debug::alert('Module %' . $moduleName . ' active, but could not be loaded.', 'f');
				} else {
					self::$activeModules[] = $currentModule;
					// Adding path parameter order for current module
					self::$pathParamOrders[$moduleName] = $currentModule->pathParamOrder;

					// Now loading the model
					$moduleClass = '\\Arembi\Xfw\\Module\\' . $moduleName;
					if ($moduleClass::hasModel()) {
						// Setting the proper model file name
						$modelFileName = 'model.' . $moduleName . '.php';
						$modelFilePath = 'modules' . DS . $moduleName . DS . $modelFileName;

						$loaded = false;

						if (file_exists(ENGINE . DS . $modelFilePath)) {
							include(ENGINE . DS . $modelFilePath);
							Debug::alert('Base model for module %' . $moduleName . ' successfully loaded and activated.', 'o');
							$loaded = true;
						} else {
							Debug::alert('Base model for module %' . $moduleName . ' could not be loaded.', 'n');
						}

						// Looking for overrides
						if (file_exists(SITES . DS . DOMAIN . DS . $modelFilePath)) {
							include(SITES . DS . DOMAIN . DS . $modelFilePath);
							Debug::alert('Override for model of module %' . $moduleName . ' successfully loaded and activated.', 'o');
							$loaded = true;
						} elseif($loaded) {
							class_alias('\\Arembi\Xfw\\Module\\' . $moduleName . 'BaseModel', '\\Arembi\Xfw\\Module\\' . $moduleName . 'Model');
						}

						if (!$loaded) {
							Debug::alert('Model for module %' . $moduleName . ' could not be loaded.', 'f');
						}
					}
				}
			} else {
				Debug::alert('Module %' . $moduleName . ' installed, but not active.', 'i');
			}
		}

		// Loading domain specific models
		$domainSpecificModels = glob(DOMAIN_DIRECTORY . DS . 'models' . DS . '*.php');

		foreach ($domainSpecificModels as $m) {
			include($m);
		}

	}



	private static function loadInstalledModuleData()
	{
		/*
		 * The document and the head modules are non-optional
		 * they are not listed in the db, so adding them manually
		 * */
		$document = new \stdClass();
		$document->ID = 0;
		$document->name = 'document';
		$document->class = 'system';
		$document->active = 1;
		$document->priority = 0;
		$document->pathParamOrder = null;

		$head = new \stdClass();
		$head->ID = 0;
		$head->name = 'head';
		$head->class = 'system';
		$head->active = 1;
		$head->priority = 99;
		$head->pathParamOrder = null;

		$image = new \stdClass();
		$image->ID = 0;
		$image->name = 'image';
		$image->class = 'system';
		$image->active = 1;
		$image->priority = 1;
		$image->pathParamOrder = null;

		self::$installedModules = [
			$document,
			$head,
			$image
		];

		// Retrieving the modules stored in the database
		self::$installedModules = array_merge(self::$installedModules, self::$model->getInstalledModules());
	}



	public static function loadModuleAddon(string $module, string $addon)
	{
		$addon = strtolower($addon);
		$moodule = strtolower($module);

		$addons = Config::_('moduleAddons');

		// If a module has been requested which hasn't been installed, or
		// there is no such addon, we return false
		if (!self::isInstalledModule($module) || !in_array($addon, array_keys($addons))) {
			return false;
		}

		$addonName = $addons[$addon];
		$addonFilePath = 'modules' . DS . $module . DS . $addon . '.' . $module . '.php';

		$loaded = false;

		if (file_exists(ENGINE . DS . $addonFilePath)) {
			include(ENGINE . DS . $addonFilePath);
			$loaded = true;
			Debug::alert('Base module addon ' . $addonName . ' for %' . $module . ' successfully loaded.', 'o');
		} else {
			Debug::alert('Base module addon ' . $addonName . ' for %' . $module . ' could not be loaded.', 'n');
		}

		// Looking for overrides
		if (file_exists(SITES . DS . DOMAIN . DS . $addonFilePath)) {
			include(SITES . DS . DOMAIN . DS . $addonFilePath);
			Debug::alert('Override for module addon ' . $addonName . ' for %' . $module . ' successfully loaded and activated.', 'o');
			$loaded = true;
		} elseif ($loaded) {
			// Creating an alias for ModuleNameBase to ModuleName,
			// because the system uses only ModuleName classes
			class_alias('\\Arembi\Xfw\\Module\\' . $addon . '_' . $module . 'Base', '\\Arembi\Xfw\\Module\\' . $addon . '_' . $module);

			// No override found, trying to create an alias to the base module
			Debug::alert('Module addon ' . $addonName . ' for module %' . $module . ' successfully loaded and activated.', 'o');
		}

		return $loaded;
	}


	private static function loadThirdPartyLibs()
	{
		// Composer Autoload
		$composerAutoloadFile = ENGINE . DS . 'vendor' . DS . 'autoload.php';
		if (file_exists($composerAutoloadFile))	{
			require($composerAutoloadFile);
			Debug::alert('Composer libraries loaded.', 'o');
		} else {
			Debug::alert('Cannot load Composer libraries.', 'e');
		}
	}


	public static function getLang()
	{
		return self::$lang;
	}


	public static function setLang($lang)
	{
		self::$lang = $lang;
	}


	public static function lang($lang = null)
	{
		if (empty($lang)) {
			return self::$lang;
		} else {
			self::$lang = $lang;
		}
	}


	public static function moduleInfo($identifier, $value)
	{
		$i = 0;
		$l = count(self::$installedModules);
		$found = false;

		while ($i < $l && !$found) {
			if (isset(self::$installedModules[$i]->$identifier)
				&& self::$installedModules[$i]->$identifier === $value) {
					$found = true;
			} else {
				$i++;
			}
		}
		return $found ? self::$installedModules[$i] : false;
	}


	// Function to check whether a module is installed for on the current domain
	public static function isInstalledModule($module)
	{
		$isInstalled = false;
		foreach (self::$installedModules as $m) {
			if ($m->name === $module) {
				$isInstalled = true;
				break;
			}
		}

		return $isInstalled;
	}


	public static function getActiveModules($attribute = false)
	{
		return $attribute ? array_column(self::$activeModules, $attribute) : self::$activeModules;
	}


	public static function getPrimaryModules()
	{
		return array_filter(self::$activeModules, function ($m) {
			return $m->class == 'p';
		});
	}


	// Register all instantiated modules as an array
	public static function registerModule(&$moduleObject)
	{
		self::$registeredModules[self::$registeredModuleID] = $moduleObject;
		return self::$registeredModuleID ++;
	}


	// Returns the whole record of registered modules
	public static function getRegisteredModules()
	{
		return self::$registeredModules;
	}


	public static function getRegisteredModule($ID)
	{
		return self::$registeredModules[$ID] ?? false;
	}


	public static function getDocument()
	{
		return self::$registeredModules[0] ?? false;
	}

	// Implement it if needed
	//public static function unlistModule(string $name, int $ID) {}

	// Returns the path parameter order for the requested module
	public static function getPathParamOrder($moduleName)
	{
		// The extensions like control panel  (CP_) and form handler (FH_) have no different PPO
		// They get the basemodule's PPO
		$parts = explode('_', $moduleName, 2);

		if (in_array(strtolower($parts[0]), array_keys(Config::_('moduleAddons')))) {
			$moduleName = $parts[1];
		}

		return self::$pathParamOrders[$moduleName];
	}


	// Halt & Catch Fire
	// Stops the execution and optionally displays a message
	public static function hcf($message = '')
	{
		print_r($message);
		Debug::render();
		exit;
	}


	public static function AMP($value = null)
	{
		if (!empty($value)) {
			self::$AMP = $value;
		} else {
			return self::$AMP;
		}
	}


	public static function getUsers()
	{
		return self::$model->getUsers();
	}


	public static function getUsersByDomain($domain = 'current')
	{
		return self::$model->getUsersByDomain($domain);
	}


}
