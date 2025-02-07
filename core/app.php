<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Module\Document;
use Arembi\Xfw\Misc;

abstract class App {

	// The App's model
	private static $model;

	// The list of core modules, ONLY THESE WILL BE LOADED with the core
	private static $coreModules;

	// Modules that have been installed to the current domain
	private static $installedModules;

	// Active modules are the successfully installed and loaded modules
	private static $activeModules;

	// $registeredModules keeps track of which modules have already been instantiated
	// by the system, it holds the references to all embedded modules
	private static $registeredModules;

	// Autoincrementing inner identifier for embedded modules
	private static $registeredModuleId;

	// Path parameter translation rules for modules
	private static $pathParamOrders;

	// Language
	private static $lang;

	// Makes it possible to manually hide the debug panel
	private static $debugSuppressed;


	public static function init()
	{
		self::$model = null;
		
		self::$coreModules = [
			'model',
			'settings',
			'language',
			'session',
			'module',
			'input_handler',
			'router',
			'user'
		];
		
		self::$installedModules = [];
		self::$activeModules = [];
		self::$registeredModules = [];
		self::$registeredModuleId = 0;
		self::$pathParamOrders = [];
		self::$lang = '';
		self::$debugSuppressed = false;


		// Loading configuration
		self::loadConfigAndDebug();
		
		// Loading scripts from the engine include directory
		self::loadScriptsFromDirectory(ENGINE_DIR . DS . 'include') ;
		
		// Load libraries from other vendors
		self::loadThirdPartyLibs();
		
		// Load core controllers and models
		self::loadCore();
		
		Debug::init();
		Debug::alert('Configuration loaded');
		Debug::alert('Core loaded');

		// Starting a timer to measure page load speed
		$pageloadTimer = new Misc\Timer();
		$pageloadTimer->mark();

		// Apply charset settings
		mb_language(Config::get('mbLanguage'));
		mb_internal_encoding(Config::get('mbInternalEncoding'));
		
		// Establishing database connection and interface
		Database::init();
		
		// Initializing the language module
		Language::init();
		
		// Determine environment (protocol, domain)
		Router::init();
		Router::getEnvironment();
		
		if (Config::get('debugMode') && IS_LOCALHOST) {
			error_reporting(E_ALL);
		}

		Debug::alert('Router initialized');

		// Loading Settings (settings stored in the database)
		Settings::init();
		Debug::alert('Settings loaded');

		// Loading scripts from the domain's include directory
		self::loadScriptsFromDirectory(DOMAIN_DIR . DS . 'include') ;

		// Initializing sessions
		Session::init();
		Session::start();
		
		Debug::alert('Session started');

		// Setting the model of the App class
		self::$model = new AppModel();

		// Checking the session's user
		self::populateSessionUser();
		
		$websiteDatabase = Settings::get('database');

		// Connecting to the websites database if necessary
		if (isset($websiteDatabase['name'], $websiteDatabase['connection'])) {
			Database::connect($websiteDatabase['name'], $websiteDatabase['connection']);
		} elseif (!empty($websiteDatabase)) {
			Debug::alert('Missing website database info, cannot connect.', 'f');
		}

		// Load the modules with ModuleCore
		self::loadInstalledModules();

		Debug::alert('Modules loaded');

		// Load routes, links, redirects
		Router::loadData();

		/*
		 * The system requires the document layout and a primary module from the router
		 * For the primary module, the action is also required
		 * */
		$matchedRoute = Router::parseRoute();

		Debug::alert('URI parsed');

		Debug::alert('Active user: ' . $_SESSION['user']->get('username'));

		// Initializing inner autoincrementing ID
		self::$registeredModuleId = 0;

		//Setting the module option for the document module
		$documentModuleOptions = [
			'layout'=>$matchedRoute['documentLayout'],
			'primaryModule'=>$matchedRoute['primary'],
			'primaryModuleOptions'=>$matchedRoute['options'],
			'parentModule'=>null
		];

		// The response is simply an instance of a document module
		$response = new Document($documentModuleOptions);

		Debug::alert('Document ready to render');

		// Start the layout loop and show the result
		$response->processLayout();

		// Generate HTML output
		$response->render();

		// Final time marker
		$pageloadTimer->mark();
		Debug::alert('Page load time: ' . $pageloadTimer->getFullTime() . 's.', 'i');

		// Memory usage information
		Debug::alert('Memory usage: ' . number_format(memory_get_usage() / (1024 * 1024), 4) . ' MB', 'i');
		Debug::alert('Memory peak usage: ' . number_format(memory_get_peak_usage() / (1024 * 1024), 4) . ' MB', 'i');

		if (!self::isDebugSuppressed()) {
			// Show debug messages
			Debug::render();
		}
	}


	private static function loadConfigAndDebug()
	{
		$configFile = CORE_DIR . DS .'config.php';
		if (file_exists($configFile)) {
			require($configFile);
			Config::init();
		} else {
			die ('Inappropriate configuration, please contact the adminisrator');
		}
		$debugFile = CORE_DIR . DS .'debug.php';
		if (file_exists($debugFile)) {
			require($debugFile);
		} else {
			die ('An error occured during initialization.');
		}
	}


	private static function loadCore()
	{
		// The base models
		$files = glob(ENGINE_DIR . DS . 'models' . DS . '*.php');
		foreach ($files as $file) {
			include($file);
		}

		// The core module files
		foreach (self::$coreModules as $module) {
			// The controller
			$controllerFileName = $module . '.php';
			$controllerFile = CORE_DIR . DS . $controllerFileName;

			if (file_exists($controllerFile)) {
				include($controllerFile);
				Debug::alert('Core controller %' .  $module . ' successfully loaded.', 'o');
			} else {
				Debug::alert('Core controller %' .  $module . ' could not be loaded.', 'f');
			}

			// The model
			$modelFileName = 'model.' . $module . '.php';
			$modelFile  = CORE_DIR . DS . $modelFileName;

			if (file_exists($modelFile)) {
				require($modelFile);
				Debug::alert('Model for core module %' .  $module . ' successfully loaded.', 'o');
			}
		}

		// Additionally we have to load the model of appCore here, because it extends the previously loaded ModelCore class
		$modelFileName = 'model.app.php';

		if (file_exists(CORE_DIR . DS . $modelFileName)) {
			require(CORE_DIR . DS . $modelFileName);
			Debug::alert('Model for core module %app successfully loaded.', 'o');
		} else {
			Debug::alert('Model for core module %app could not be loaded.', 'w');
		}
	}


	private static function loadScriptsFromDirectory(string $directory)
	{
		if (file_exists($directory)) {
			$dirIterator = new \RecursiveDirectoryIterator($directory);
			$iterator = new \RecursiveIteratorIterator($dirIterator, \RecursiveIteratorIterator::SELF_FIRST);

			$includes = [];
			foreach ($iterator as $i=>$file) {
				if ($file->isFile()) {
					// interfaces have to be loaded first
					$iPos = strrpos($file, 'interface.');
					if ($iPos !== false && strrpos($file, '/') < $iPos) {
						array_unshift($includes, $file);
					} else {
						array_push($includes, $file);
					}
				}
			}

			foreach ($includes as $file) {
				include($file);
			}

		} else {
			Debug::alert('Include directory does not exists at' . $directory, 'n');
		}
	}


	public static function populateSessionUser()
	{
		if (!empty($_SESSION['user'])) {
			return false;
		}
		
		$autoLoginConfig = Config::get('localhostAutoLogin');
		
		if (IS_LOCALHOST && $autoLoginConfig['enabled']) {
			$user = new User('xfwuser', 'generic');
			$user->set('domain', DOMAIN)
				->set('id', 0)
				->set('firstName', 'User')
				->set('lastName', 'Xfw')
				->set('userGroup', 'xfw')
				->set('clearanceLevel', $autoLoginConfig['clearanceLevel']);
		} else {
			$user = new User('_guest', 'generic');
			$user->set('domain', DOMAIN)
				->set('id', 0)
				->set('firstName', 'Guest')
				->set('lastName', 'user')
				->set('userGroup', 'guest')
				->set('clearanceLevel', 0);
		}
		$_SESSION['user'] = $user;
	}


	private static function loadInstalledModules()
	{
		// Get data from the database
		self::loadInstalledModuleData();

		/*
		 * Module behaviour can be modified distinctly for every domain
		 * If there is a proper file in the SITES_DIR/[domain]/modules directory, that
		 * class can extend the basic module
		 * */
		foreach (self::$installedModules as $currentModule) {
			$moduleName = $currentModule->name;

			if ($currentModule->active || $currentModule->category == 'core') {
				$moduleFilePath = 'modules' . DS . $moduleName . DS . 'module.' . $moduleName . '.php';

				$loaded = false;

				if (file_exists(ENGINE_DIR . DS . $moduleFilePath)) {
					include(ENGINE_DIR . DS . $moduleFilePath);
					$loaded = true;
					Debug::alert('Base module %' . $moduleName . ' successfully loaded.', 'o');
				} else {
					Debug::alert('Base module %' . $moduleName . ' could not be loaded.', 'n');
				}

				// Looking for overrides
				if (file_exists(SITES_DIR . DS . DOMAIN . DS . $moduleFilePath)) {
					include(SITES_DIR . DS . DOMAIN . DS . $moduleFilePath);
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

						if (file_exists(ENGINE_DIR . DS . $modelFilePath)) {
							include(ENGINE_DIR . DS . $modelFilePath);
							Debug::alert('Base model for module %' . $moduleName . ' successfully loaded and activated.', 'o');
							$loaded = true;
						} else {
							Debug::alert('Base model for module %' . $moduleName . ' could not be loaded.', 'n');
						}

						// Looking for overrides
						if (file_exists(DOMAIN_DIR . DS . $modelFilePath)) {
							include(DOMAIN_DIR . DS . $modelFilePath);
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
		$domainSpecificModels = glob(DOMAIN_DIR . DS . 'models' . DS . '*.php');

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
		$document->id = 0;
		$document->name = 'document';
		$document->class = 'system';
		$document->active = 1;
		$document->priority = 0;
		$document->pathParamOrder = null;

		$head = new \stdClass();
		$head->id = 0;
		$head->name = 'head';
		$head->class = 'system';
		$head->active = 1;
		$head->priority = 99;
		$head->pathParamOrder = null;

		$bodyStart = new \stdClass();
		$bodyStart->id = 0;
		$bodyStart->name = 'body_start';
		$bodyStart->class = 'system';
		$bodyStart->active = 1;
		$bodyStart->priority = 98;
		$bodyStart->pathParamOrder = null;

		$bodyEnd = new \stdClass();
		$bodyEnd->id = 0;
		$bodyEnd->name = 'body_end';
		$bodyEnd->class = 'system';
		$bodyEnd->active = 1;
		$bodyEnd->priority = 98;
		$bodyEnd->pathParamOrder = null;

		$image = new \stdClass();
		$image->id = 0;
		$image->name = 'image';
		$image->class = 'system';
		$image->active = 1;
		$image->priority = 1;
		$image->pathParamOrder = null;

		self::$installedModules = [
			$document,
			$head,
			$bodyStart,
			$bodyEnd,
			$image
		];

		// Retrieving the modules stored in the database
		self::$installedModules = array_merge(self::$installedModules, self::$model->getInstalledModules(DATA_DOMAIN_ID));
	}


	public static function loadModuleAddon(string $module, string $addon)
	{
		$addon = strtolower($addon);
		$module = strtolower($module);

		if (class_exists('Arembi\Xfw\Module\CP_' . $module)) {
			Debug::alert("Cannot load module addon $addon for %$module: it has already been loaded.", 'f');
			return false;
		}

		if (!self::isInstalledModule($module)) {
			Debug::alert("Cannot load module addon $addon for module %$module: module is not installed on this domain,", 'f');
			return false;
		}

		$addons = Config::get('moduleAddons');

		if (!in_array($addon, array_keys($addons))) {
			Debug::alert("Cannot load module addon $addon: addon not supported.,", 'f');
			return false;
		}
		
		$addonName = $addons[$addon];
		$addonFilePath = 'modules' . DS . $module . DS . $addon . '.' . $module . '.php';

		$loaded = false;

		if (file_exists(ENGINE_DIR . DS . $addonFilePath)) {
			include(ENGINE_DIR . DS . $addonFilePath);
			$loaded = true;
			Debug::alert('Base module addon ' . $addonName . ' for %' . $module . ' successfully loaded.', 'o');
		} else {
			Debug::alert('Base module addon ' . $addonName . ' for %' . $module . ' could not be loaded.', 'n');
		}

		// Looking for overrides
		if (file_exists(SITES_DIR . DS . DOMAIN . DS . $addonFilePath)) {
			include(SITES_DIR . DS . DOMAIN . DS . $addonFilePath);
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
		$composerAutoloadFile = ENGINE_DIR . DS . 'vendor' . DS . 'autoload.php';
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
	public static function isInstalledModule(string $module)
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


	public static function getActiveModules(string $attribute = '')
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
		self::$registeredModules[self::$registeredModuleId] = $moduleObject;
		return self::$registeredModuleId ++;
	}


	// Returns the whole record of registered modules
	public static function getRegisteredModules()
	{
		return self::$registeredModules;
	}

	// Implement it if needed
	public static function unlistModule() {}

	// Returns the path parameter order for the requested module
	public static function getPathParamOrder($moduleName)
	{
		// The extensions like control panel  (CP_) and form handler (IH_) have no different PPO
		// They get the basemodule's PPO
		$parts = explode('_', $moduleName, 2);

		if (in_array(strtolower($parts[0]), array_keys(Config::get('moduleAddons')))) {
			$moduleName = $parts[1];
		}

		return self::$pathParamOrders[$moduleName];
	}


	public static function getUsers()
	{
		return self::$model->getUsers();
	}


	public static function getUsersByDomain($domain = 'current')
	{
		return self::$model->getUsersByDomain($domain);
	}

	// Halt & Catch Fire
	// Stops the execution and optionally displays a message
	public static function hcf($message = '')
	{
		Debug::alert('HCF: ' . $message, 'f');
		Debug::render();
		exit;
	}


	private static function isDebugSuppressed()
	{
		return (self::$debugSuppressed || !Config::get('debugMode') || !IS_LOCALHOST);
	}


	public static function suppressDebug()
	{
		self::$debugSuppressed = true;
	}


}
