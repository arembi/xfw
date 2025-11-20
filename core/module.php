<?php
/*
[MODULES]

Modules are the controllers in the MVC model.
The modules are divided the following groups (classes):
	Primary
		These modules will generate the response with the content that you want to bind to a specific URL
			Examaple: static page, blog post
	Secondary
		These modules are independent from the URL (though they can use it), everything is set in their parent module
			Example: comments box, latest news
	Backend
		These modules require no respone
			Example: backup

Module extensions
	IH - Input Handler (see Input handling)
	CP - Control Panel (see Control panel)

Module params
	Can be passed on as constructor parameters
	parentModule->name f.i.: static_page
	parentModule->id f.i.: 19 means the static_page with the ID 19 (not the sys_module ID)

Recursion
	By default, the system will go through every layout, and load the moduleVars and embedded modules.
	However, if you, for instance need only the layout of the module,
	you can do it by setting the $params['recursive'] to 'no'.

Actions
	Actions are methods of module classes. They can be triggered via HTTP requests.

[LAYOUTS]

Layouts are the views in the MVC model, located in the site's layouts directory
the file hierarchy looks like this:
		sites
			|- domain1.com
				|- modules
				|- layouts
					|- layout1
						|- variant1.php
						|- variant2.php
					|- layout2
						|- variant1.php
						|- variant2.php
			|- domain2.com
				|- modules
				|- layouts
					|- layout1
						|- variant1.php
						|- variant2.php
					|- layout2
						|- variant1.php
						|- variant2.php

[HOW THE SYSTEM WORKS]

Primary modules can be assigned to an URI. After the environment has been
detected, and the system is set up, the router tries to find a match to the
request.
If that succeeds, a document module is instantiated and a loop starts which
continously loads the module layouts, inserts module layout variables and loads
embedded modules. The loop ends when there are no more recursive embedded modules left.

The LOAD ORDER of the embedded modules can be modified by setting their priorty
( stored in the sys_module table). This is most important for the head module,
since its layout variables depend on the main content of the page, but the HTML
has to be put before them in the output.


!!! DO NOT FORGET !!!
If you instatiate a module without giving it an ID,
remember to set the params['id']. (If not, it will get one in its init())

!!! DO NOT FORGET !!!
Each module class has to call the invokeModel() and the loadPathParams() functions
in their init() function in order to be able to use the database and have access
to the path parameters.
Had to move those 2 functions out of the constructor, because we need to use
them before the parent's constructor is called.

The module addons inherit the path parameters (and path parameter order)
from their main modules.
*/

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Misc;
use Arembi\Xfw\Module\Head;
use Arembi\Xfw\Module\Image;
use ReflectionClass;

abstract class ModuleBase {
	
	protected static $autoloadModel; // Whether the autoloader should look for a model
	
	protected $moduleName; // The module's name
	protected $noAddonName; // The module's name without the addon's prefix
	protected $rId; // Registration ID, used by the system to identify instantiated modules
	protected $params; // Parameters used beyond the module's basic functionality
	protected $objectClass; // The module class's name
	protected $addonType; // Module addon type, or false if it is not an addon
	protected $parentModule; // Reference to the module which embedded this
	protected $model; // The model module extension
	protected $error; // Error that can prevent layout processing
	protected $action; // The action to be execeuted upon request
	protected $autoAction; // Whether an anction has to be triggered for the module
	protected $layout; // Layout used to render the module
	protected $layoutVariant; // Layout variant used to render the module
	protected $layoutHtml; // The HTML output for the module after processing its layout
	protected $layoutProcessed; // Shows whether the module's layout has already been processed
	protected $layoutVariables; // Variables that can be accessed in layouts
	protected $layoutFilters; // Mutators of the printed values
	protected $embeddedModules; // The array of embedded modules in the layout
	protected $reflector; // Instance of the Reflector class
	protected $embedId; // Used for ordering of embedded modules within a layout
	protected $recursive; // Whether the loop should continue through the current layout
	protected $formData; // Data sent by forms defined within the system, used by IH module extensions
	protected $autoInit; // Whether to run init() automatically
	protected $autoFinalize; // Whether to run finalize() automatically


	public static function autoloadModel()
	{
		return static::$autoloadModel;
	}


	final public function __construct(array $params = [])
	{
		$this->reflector = new ReflectionClass($this);
		$this->objectClass = $this->reflector->getShortName();
		
		$moduleName = strtolower($this->objectClass);
		if (substr($moduleName, -4) == 'base') {
			$moduleName = substr($moduleName, 0, -4);
		};
		$this->moduleName = $moduleName;
		
		$this->noAddonName = '';
		$this->error = [
			'errorOccured'=>false,
			'message'=>''
		];
		$this->layout = $params['layout'] ?? Settings::get('defaultModuleLayout');
		$this->layoutVariant = $params['layoutVariant'] ?? Settings::get('defaultModuleLayoutVariant');
		$this->layoutProcessed = false;
		$this->layoutVariables = [];
		$this->layoutFilters = $params['layoutFilters'] ?? [];
		$this->embeddedModules = [];
		$this->embedId = 0;
		$this->parentModule = $params['parentModule'] ?? null;
		$this->recursive = $params['recursive'] ?? true;

		// Determining addon type
		$nameParts = explode('_', $this->moduleName, 2);
		if (count($nameParts) == 2) {
			if (in_array($nameParts[0], array_keys(Config::get('moduleAddons')))) {
				$this->addonType = $nameParts[0];
				$this->noAddonName = $nameParts[1];
			} else {
				$this->addonType = null;
				$this->noAddonName = $this->moduleName;
			}
		} else {
			$this->addonType = null;
			$this->noAddonName = $this->moduleName;
		}

		$this->action = $params['action'] ?? Router::getRequestedAction();
		
		/*
		Code that should run every time the module is instantiated
		shall be put into its init() and finalize() functions
		init() runs right after instantiation
		finalize() runs after actions
		Embedded modules will run init() and finalize() by default.
		The module addons will not run init() and finalize() by default,
		This can be overriden via a constructor parameter, or by assigning a non-null value to the class properties off the addon
		*/

		$this->autoInit = $params['autoInit'] ?? $this->autoInit ?? ($this->addonType === null);
		$this->autoAction = $params['autoAction'] ?? $this->autoAction ?? false;
		$this->autoFinalize = $params['autoFinalize'] ?? $this->autoFinalize ?? false;
		
		$this->params = $params;
		$this->params['id'] ??= 0;

		/*
		For unique functionality on a request, use actions)
		If you want to use a model, create a class in the model.{modulename}.php file,
		and call the invokeModel() in the controller's init() function
		To access URL parameters call loadPathParams() within the module class
		*/
		
		if ($this->autoInit) {
			$this->init();
		}

		if ($this->autoAction) {
			if ($this->action) {
				$this->executeAction($this->action);
			}
		}

		if ($this->autoFinalize) {
			$this->finalize();
		}

	}


	public function __toString(): string
	{
		return $this->processLayout()->getLayoutHtml() ?? '';
	}


	protected function init(){}


	protected function finalize(){}


	protected function moduleName(): string
	{
		return $this->moduleName;
	}
	
	
	protected function objectClass(): string
	{
		return $this->objectClass;
	}
	
	
	protected function action(?string $action): string|ModuleBase
	{
		if ($action === null) {
			return $this->action;
		}
		$this->action = $action;
		return $this;
	}


	protected function autoInit(?bool $trigger = null): bool|ModuleBase
	{
		if ($trigger === null) {
			return $this->autoInit;
		}
		$this->autoInit = $trigger;
		return $this;
	}


	protected function autoAction(?bool $trigger = null): bool|ModuleBase
	{
		if ($trigger === null) {
			return $this->autoAction;
		}
		$this->autoAction = $trigger;
		return $this;
	}


	protected function autoFinalize(?bool $trigger = null): bool|ModuleBase
	{
		if ($trigger === null) {
			return $this->autoFinalize;
		}
		$this->autoFinalize = $trigger;
		return $this;
	}


	protected function executeAction(string $action): void
	{
		$actionMethod = $action . 'Action';
		if (method_exists($this, $actionMethod)) {
			$this->$actionMethod();
			Debug::alert('Action ' . $action . ' for %' . $this->objectClass . ' successfully triggered.', 'o');
		} else {
			Debug::alert('Action ' . $action . ' for %' . $this->objectClass . ' could not be triggered.' , 'f');
		}
	}


	protected function error(?string $message = null): array|ModuleBase
	{
		if ($message === null) {
			return $this->error;
		}
		$this->error = ['errorOccured'=>($message != ''), 'message'=>$message];
		return $this;
	}


	protected function layout(?string $layout = null): string|ModuleBase
	{
		if ($layout === null) {
			return $this->layout;
		}
		$this->layout = $layout;
		return $this;
	}


	protected function layoutVariant(?string $variant = null): string|ModuleBase
	{
		if ($variant === null) {
			return $this->layoutVariant;
		}
		$this->layoutVariant = $variant;
		return $this;
	}


	/*
	* The layout processing covers the followings:
	* - put the layoutVariables into the layout
	* - if recursive, load embedded modules
	* */
	public function processLayout(): ModuleBase
	{
		if ($this->error()['errorOccured']) {
			Debug::alert("Cannot process layout of %$this->moduleName #" . $this->params['id'] . ': ' . $this->error()['message'], 'f');
			$this->layoutHtml = '';
			return $this;
		}

		// Register the module in the system
		$this->rId = App::registerModule($this);

		// Load the module's layout
		$layout = $this->findLayoutFile($this->layout, $this->layoutVariant);

		// If the requested layout could not be loaded, we try to load the default
		if ($layout['layoutFile'] === null) {
			$layout = $this->findLayoutFile(Settings::get('defaultModuleLayout'));
		}

		// Loading layout file
		if ($layout['layoutFile']) {
			extract($this->layoutVariables);
			ob_start();
			require($layout['layoutFile']);
			$this->layoutHtml = ob_get_contents();
			ob_end_clean();
		}

		if (!empty($this->layoutHtml)) {
			// Initializing embedded modules (if enabled)
			if ($this->recursive) {
				// Loading embedded modules
				if (!empty($this->embeddedModules)) {
					// Sorting the embedded modules by their priority, ascending
					$this->embeddedModules = Misc\md_array_sort($this->embeddedModules, 'priority');

					// Loading the embedded modules
					foreach ($this->embeddedModules as $module) {
						$embeddedModule = $this->invokeModule($module['params']['name'], $module['params']);
						
						if ($embeddedModule !== false) {
							// The module has been successfully loaded
							$embedHtml = $embeddedModule->__toString() ?? '';
						} else {
							$embedHtml = '';
						}

						// Replacing the placeholder with the embedded HTML
						$this->layoutHtml = str_replace('{%' . $module['embedId'] . '%}', $embedHtml, $this->layoutHtml);
					}
				}
			}
		}

		$this->layoutProcessed = true;
		return $this;
	}


	protected function findLayoutFile(string $layout, ?string $variant = null)
	{
		$variant ??= $layout;
		
		if (file_exists(DOMAIN_DIR . DS . 'layouts' . DS . $this->noAddonName . DS . $layout . DS . $variant . '.php')) {
			$layoutDir = DOMAIN_DIR . DS . 'layouts' . DS . $this->noAddonName . DS . $layout;
			$layoutFile = $layoutDir . DS . $variant . '.php';
		} elseif (file_exists(ENGINE_DIR . DS . 'layouts' . DS . $this->noAddonName . DS . $layout . DS . $variant . '.php')) {
			// Trying to fallback to the base module layout
			$layoutDir = ENGINE_DIR . DS . 'layouts' . DS . $this->noAddonName . DS . $layout;
			$layoutFile = $layoutDir . DS . $variant . '.php';
		} else {
			$layoutFile = null;
			$layoutDir = null;
			Debug::alert('Layout/variant [' . $layout . '/' . $variant . '] for module %' . $this->moduleName . ' not found.', 'f');
		}

		return ['layoutFile' => $layoutFile, 'layoutDir' => $layoutDir];
	}


	protected function embed(string $name, array $params = [])
	{
		if (!$this->recursive){
			return false;
		}
		
		$params['id'] ??= 0;
		$params['autoInit'] ??= true;
		$params['autoFinalize'] ??= true;

		if (empty($name)) {
			Debug::alert('Trying to embed an unspecified module, will be ignored', 'n');
		} else {
			$params['name'] = $name;
			// Preventing infinite loop: the embedded module with the same name as its parent has to have a different ID from the parent
			if ($this->moduleName != $params['name']
				|| !array_key_exists($params['name'] . '#' . $params['id'] , App::getRegisteredModules())) {
				
				// Adding the embedded module to the list
				$module = App::getActiveModules()->first(function($value, $key) use ($params) {
					return $value->name === $params['name'];
				});

				if (!empty($module)) {
					$this->embeddedModules[] = [
						'embedId' => $this->embedId,
						'priority' => $module->priority,
						'params' => $params,
					];

					// Set placeholder in the layout
					echo '{%' . $this->embedId . '%}';

					// Adjust embedID
					$this->embedId++;
				} else {
					Debug::alert('Embedded module %' . $params['name'] . ' is not active on this domain.', 'f');
				}
			} else {
				Debug::alert('Embedded module %' . $params['name'] . ' could not be loaded. You cannot embed the same module with the same id (' . $params['id'] . ').', 'f');
			}
		}
	}


	protected function invokeModule(string $moduleName, array $params)
	{
		// Only active module classes can be instantiated
		if (App::isActiveModule($moduleName)) {
			$params['parentModule'] = $this;
			$moduleName = '\\Arembi\\Xfw\\Module\\' . $moduleName;

			$module = new $moduleName($params);

			return $module;
		} else {
			Debug::alert('Module %' . $moduleName . ' could not be found.', 'f');
			return false;
		}
	}


	// Adds the variable to the layout variables
	public function lv(string $var, $value)
	{
		$this->layoutVariables[$var] = $value;
		return $this;
	}

	
	protected function invokeModel()
	{
		// Attempting to activate the module's model
		$modelName = $this->reflector->getShortName() . 'Model';

		// Module extensions use the same model as their parent class
		$parts = explode('_', $modelName, 2);
		if (in_array(strtolower($parts[0]), array_keys(Config::get('moduleAddons')))) {
			$modelName = $parts[1];
		}
		// Adding namespace
		$modelClass = '\\Arembi\\Xfw\\Module\\' . $modelName;

		// Instantiating and initialising the module's model
		if (class_exists($modelClass)) {
			$this->model = new $modelClass();
			if (method_exists($this->model, 'init')) {
				$this->model->init();
			}
		} else {
			Debug::alert('Could not invoke model ' . $modelName . '.', 'f');
		}
	}


	/* 
		Prints $value (in the layouts) after applying the layoutFilters
		If $value is passed as an array, it is assumed to contain the
		different language-variations
		Filters can be given as a single or an array of strings
		A module can impose the layout filters via the module params
	*/
	protected function print(string|array|null $value, ?array $mutators = null)
	{	
		$value ??= '';
		$lang = '';
		$printedValue = '';
		$filters = [];
		
		if (is_array($value)) {
			$lang = $mutators['lang'] ?? App::getLang();
			if (isset($value[$lang])) {
				$printedValue = $value[$lang];
			}
		} else {
			$printedValue = $value;
		}

		if (isset($mutators['filters'])) {
			$filters = (array) $mutators['filters'];
		}

		// Inheriting the filter demands from the module
		$filters = array_merge($filters, $this->layoutFilters);

		foreach ($filters as $filter) {
			$filterClass = '\\Arembi\\Xfw\\Inc\\Filter\\' . $filter . 'LayoutFilter';
			if (class_exists($filterClass)) {
				$filter = new $filterClass();
			}
			$printedValue = $filter->filter($printedValue);
		}

		echo $printedValue;
	}


	protected function a(string $href, string|array $anchor = '', array $params = [], bool $embed = true)
	{
		$linkParams = [
			'href'=>$href,
			'anchor'=>$anchor,
			...$params
		];

		if ($embed) {
			$this->embed('link', $linkParams);
		} else {
			$a = new Link($linkParams);
			$a->finalize();
			return $a->__toString();
		}
	}


	protected function img(string $src, array $attributes = [], bool $embed = true)
	{
		$keys = array_keys($attributes);
		$htmlKeys = array_map(function($key) {
			$newKey = mb_strtolower($key);
			if (mb_strpos($newKey, 'html') === 0) {
				$newKey = mb_substr($newKey, 4);
			}
			$newKey = 'html' . mb_ucfirst($newKey);
			return $newKey;
		}, $keys);
		$htmlAttributes = array_combine($htmlKeys, $attributes);
		$imageAttributes = [
			'src'=>$src,
			...$htmlAttributes
		];
		
		if ($embed) {
			$this->embed('image', $imageAttributes);
		} else {
			$img = new Image($imageAttributes);
			$img->finalize();
			return $img->__toString();
		}
	}


	public function css(string $url): void
	{
		Head::addCss($url);
	}


	public function js(
		string $src = '',
		string $type = '',
		bool $async = false,
		bool $defer = false,
		string $crossorigin = '',
		string $integrity = '',
		string $nomodule = '',
		string $referrerpolicy = '',
		string $content = ''
	): void
	{
		Head::addJs($src, $type, $async, $defer, $crossorigin, $integrity, $nomodule, $referrerpolicy, $content);
	}


	public function getLayoutHtml(): string
	{
		return $this->layoutHtml;
	}


	public function render(): void
	{
		echo $this->layoutHtml;
	}


	// Transfers the parameters set in the URI to the module params
	protected function loadPathParams()
	{
		$pathParams = Router::getPathParams();

		$pathParamOrder = Router::getMatchedRoutePpo() ?? App::getPathParamOrder($this->moduleName) ?? [];

		// Assigning the pathParams to the params
		// Path params WILL NOT OVERRIDE already existing module params
		foreach ($pathParamOrder as $key => $value) {
			if (!isset($this->params[$value])) {
				if (!empty($pathParams[$key])) {
					Debug::alert('[Router] the parameter \'' . $value . '\' was set to \'' . $pathParams[$key] . '\' via URL.', 'o');
					$this->params[$value] = $pathParams[$key];
				} else {
					Debug::alert('[Router] the parameter \'' . $value . '\' was not set via URL.', 'w');
					$this->params[$value] = null;
				}
			} else {
				Debug::alert('[Router] the parameter ' . $value . ' was already set, will not be overriden by the URL.');
			}
		}
	}


	public function parent(): ModuleBase
	{
		return $this->parentModule;
	}


	public function children(): array
	{
		return $this->embeddedModules;
	}


	public function child(int $n): ModuleBase|null
	{
		return $this->embeddedModules[$n] ?? null;
	}


	public function getFormData()
	{
		return $this->formData;
	}


	public function setFormData($data)
	{
		$this->formData = $data;
		return $this;
	}


	public function __destruct(){}
}
