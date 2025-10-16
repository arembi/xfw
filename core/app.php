<?php

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Module\Document;
use Arembi\Xfw\Misc\Timer;
use Arembi\Xfw\Inc\Seo;
use Illuminate\Support\Collection;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;


abstract class App {

	private static $model;

	private static $coreModules;

	private static $installedModules;

	private static $activeModules;

	// Keeps track of which modules have already been instantiated
	// by the system, it holds the references to all embedded modules
	private static $registeredModules;

	// Autoincrementing ID for embedded modules
	private static $registeredModuleId;

	private static $pathParamOrders;

	private static $lang;


	public static function init()
	{
		self::loadThirdPartyLibraries(ENGINE_DIR);
		
		self::$model = null;
		self::$coreModules = [
			'filesystem',
			'model',
			'settings',
			'language',
			'session',
			'module',
			'input_handler',
			'router',
			'user'
		];
		self::$installedModules = new Collection();
		self::$activeModules = new Collection();
		self::$registeredModules = [];
		self::$registeredModuleId = 0;
		self::$pathParamOrders = [];
		self::$lang = '';
		
		self::loadConfigAndDebug();
		
		self::loadScriptsFromDirectory(ENGINE_DIR . DS . 'include') ;

		self::loadCore();
		self::$model = new AppModel();

		Debug::init();
		Debug::alert('Configuration loaded');
		Debug::alert('Core loaded');

		$pageloadTimer = new Timer();
		$pageloadTimer->mark();

		mb_language(Config::get('mbLanguage'));
		mb_internal_encoding(Config::get('mbInternalEncoding'));

		Database::init();
		
		Language::init();
		
		Router::init();
		Debug::alert('Router initialized');

		Input_Handler::init();

		Router::getEnvironment();
		Debug::alert('Environment recognized.');

		if (Config::get('debugMode') && IS_LOCALHOST) {
			error_reporting(E_ALL);
			Debug::allow();
		}

		self::loadThirdPartyLibraries(DOMAIN_DIR);

		FS::addLocalFilesystem('sys', '/');
		FS::addLocalFilesystem('site', DOMAIN_DIR);

		Settings::init();
		Debug::alert('Settings loaded');

		self::loadScriptsFromDirectory(DOMAIN_DIR . DS . 'include') ;

		Session::init();
		Session::start();
		Debug::alert('Session started');
		
		self::populateSessionUser();
		
		$websiteDatabase = Settings::get('database');

		if (isset($websiteDatabase['name'], $websiteDatabase['connection'])) {
			Database::connect($websiteDatabase['name'], $websiteDatabase['connection']);
		} elseif (!empty($websiteDatabase)) {
			Debug::alert('Missing website database info, cannot connect.', 'f');
		}

		self::loadInstalledModules();
		Debug::alert('Modules loaded.');

		Router::loadData();
		
		Router::processInput();
		
		Router::serveFiles();

		/*
		TODO Router::autoRedirect();
		*/

		/*
		 * The system requires the document layout and a primary module from the router
		 * For the primary module, the action is also required
		 * */
		$matchedRoute = Router::parsePath();
		Debug::alert('URI parsed');
		Debug::alert('Active user: ' . $_SESSION['user']->get('username'));

		Seo::init();

		$documentModuleParams = [
			'parentModule'=>null,
			'layout'=>$matchedRoute['documentLayout'],
			'layoutVariant'=>$matchedRoute['documentLayoutVariant'],
			'primaryModule'=>$matchedRoute['primary'],
			'primaryModuleParams'=>['autoAction'=>true, ...$matchedRoute['params']]
		];

		$response = new Document($documentModuleParams);
		
		Debug::alert(
			'Document Layout ready for processing. Layout / variant in use: '
			. '['
			. $documentModuleParams['layout']
			. ' / '
			. $documentModuleParams['layoutVariant']
			. '].'
		);

		$response->processLayout();

		$response->render();

		self::debugModuleInfo();

		$pageloadTimer->mark();
		Debug::alert('Page load time: ' . $pageloadTimer->getFullTime() . 's.', 'i');

		Debug::alert('Memory usage: ' . number_format(memory_get_usage() / (1024 * 1024), 4) . ' MB', 'i');
		Debug::alert('Memory peak usage: ' . number_format(memory_get_peak_usage() / (1024 * 1024), 4) . ' MB', 'i');

		Debug::render();
	}


	private static function loadThirdPartyLibraries(string $autoloaderDirectoryPath)
	{
		$composerAutoloadFilePath = $autoloaderDirectoryPath . DS . 'vendor' . DS . 'autoload.php';

		if (file_exists($composerAutoloadFilePath))	{
			require($composerAutoloadFilePath);
		} else {
			exit('An error occured.');
		}
	}


	private static function loadConfigAndDebug()
	{
		$configFile = CORE_DIR . DS .'config.php';
		if (file_exists($configFile)) {
			require($configFile);
			Config::init();
		} else {
			exit('Inappropriate configuration, please contact the adminisrator.');
		}
		$debugFile = CORE_DIR . DS .'debug.php';
		if (file_exists($debugFile)) {
			require($debugFile);
		} else {
			exit('An error occured during initialization.');
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
			$dirIterator = new RecursiveDirectoryIterator($directory);
			$iterator = new RecursiveIteratorIterator($dirIterator, RecursiveIteratorIterator::SELF_FIRST);

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
		self::loadInstalledModuleData();

		/*
		 * Module behaviour can be modified distinctly for every domain
		 * If there is a proper file in the SITES_DIR/[domain]/modules directory, that
		 * class can extend the basic module
		 * */
		foreach (self::$installedModules as $currentModule) {
			$currentModule->loadedFrom = '';
			$currentModule->modelLoadedFrom = '';

			$moduleName = $currentModule->name;
			
			if ($currentModule->active || $currentModule->category == 'core') {
				$moduleFilePath = 'modules' . DS . $moduleName . DS . 'module.' . $moduleName . '.php';

				$loaded = false;

				if (file_exists(ENGINE_DIR . DS . $moduleFilePath)) {
					include(ENGINE_DIR . DS . $moduleFilePath);
					$loaded = true;
					$currentModule->loadedFrom = 'base';
				}

				// Looking for site-specific overrides
				if (file_exists(SITES_DIR . DS . DOMAIN . DS . $moduleFilePath)) {
					include(SITES_DIR . DS . DOMAIN . DS . $moduleFilePath);
					$loaded = true;
					$currentModule->loadedFrom = 'site';
				} elseif ($loaded) {
					// Creating an alias for ModuleNameBase to ModuleName,
					// because the system uses only ModuleName classes
					class_alias('\\Arembi\Xfw\\Module\\' . $moduleName . 'Base', '\\Arembi\Xfw\\Module\\' . $moduleName);
				}

				if (!$loaded) {
					Debug::alert('Module %' . $moduleName . ' installed and active, but could not be loaded.', 'f');
				} else {
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
							$loaded = true;
							$currentModule->modelLoadedFrom = 'base';
						} else {
							Debug::alert('Base model for module %' . $moduleName . ' could not be loaded.', 'n');
						}

						// Looking for overrides
						if (file_exists(DOMAIN_DIR . DS . $modelFilePath)) {
							include(DOMAIN_DIR . DS . $modelFilePath);
							$loaded = true;
							$currentModule->modelLoadedFrom = 'site';
						} elseif($loaded) {
							class_alias('\\Arembi\Xfw\\Module\\' . $moduleName . 'BaseModel', '\\Arembi\Xfw\\Module\\' . $moduleName . 'Model');
						}

						if (!$loaded) {
							Debug::alert('Model for module %' . $moduleName . ' could not be loaded.', 'f');
						}
					}
					self::$activeModules->push($currentModule);
				}
			} else {
				Debug::alert('Module %' . $moduleName . ' installed, but inactive.', 'i');
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

		if (class_exists('Arembi\Xfw\Module\\' . $addon . '_' . $module)) {
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


	public static function getLang()
	{
		return self::$lang;
	}


	public static function setLang(string $lang)
	{
		self::$lang = $lang;
	}


	public static function lang(?string $lang = null)
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
		return $found ? self::$installedModules[$i] : null;
	}


	public static function debugModuleInfo()
	{
		$info = ['debugTitle'=>'Active module list'];
		$modules = self::$activeModules->toArray();
		
		usort($modules, function ($a, $b){
			return $a->name == $b->name ? 0 : ($a->name > $b->name ? 1 : -1);
		});

		foreach ($modules as $module) {
			$currentInfo = '';
			if ($module->loadedFrom) {
				$currentInfo = $module->name . ' [' . $module->loadedFrom . ']';
			}
			if ($module->modelLoadedFrom) {
				$currentInfo .= ' | model: [' . $module->modelLoadedFrom . ']';
			}
			$info[] = $currentInfo;
		}
		Debug::alert($info, 'i');
	}


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


	public static function getActiveModules(?string $attribute = null)
	{
		return $attribute === null ? self::$activeModules : self::$activeModules->select($attribute)->flatten();
	}


	public static function isActiveModule(string $moduleName)
	{
		return self::getActiveModules('name')->contains($moduleName);
	}


	public static function getPrimaryModules()
	{
		return self::$activeModules->filter(fn ($m) => $m->class == 'p');
	}


	// Register all instantiated modules in an array
	public static function registerModule(&$moduleObject)
	{
		self::$registeredModules[self::$registeredModuleId] = $moduleObject;
		return self::$registeredModuleId++;
	}


	// Returns the whole record of registered modules
	public static function getRegisteredModules()
	{
		return self::$registeredModules;
	}


	// Implement it if needed
	public static function deregisterModule() {}


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
	public static function hcf(string $message, bool $messageToDebug = true)
	{
		if ($messageToDebug) {
			Debug::alert('HCF: ' . $message, 'f');
		} else {
			echo $message;
		}
		Debug::render();
		exit;
	}
}
