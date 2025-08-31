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
						|- css
						|- img
						|- js
						|- layout1.php
					|- layout2
						|- css
						|- img
						|- js
						|- layout2.php
			|- domain2.com
				|- modules
				|- layouts
					|- layout1
						|- css
						|- img
						|- js
						|- layout1.php
					|- layout2
						|- css
						|- img
						|- js
						|- layout2.php

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
Each module class has to call the loadModel() and the loadPathParams() functions
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
use ReflectionClass;

abstract class ModuleCore {
	// Whether the autoloader should look for a model
	protected static $hasModel;

	// Registration ID, used by the system to identify instantiated modules
	protected $rId;

	// Parameters used beyond the module's basic functionality
	protected $params;

	// The module's name
	protected $name;

	// The module class's name
	protected $class;

	// Holds the module addon type, or false if it is not an addon
	protected $addonType;

	// Reference to the module which embedded this
	protected $parentModule;

	// The model
	protected $model;

	// Error that can prevent layout processing
	protected $error;

	// Whether an anction has to be triggered for the module
	protected $triggerAction;

	// Layout used to render the module
	protected $layout;

	// Layout variant used to render the module
	protected $layoutVariant;

	// The HTML output for the module after processing its layout
	protected $layoutHtml;

	// Shows whether the module's layout has already been processed
	protected $layoutProcessed;

	// Variables that can be accessed in layouts
	protected $layoutVariables;

	// Mutators of the printed values
	protected $layoutFilters;

	// Whether javascript files should be loaded automatically from the layout's folder
	protected $layoutJsAutoLoad;

	// Whether CSS files should be loaded automatically from the layout's folder
	protected $layoutCssAutoLoad;

	// The array of embedded modules in the layout
	protected $embeddedModules;

	// Instance of the Reflector class
	protected $reflector;

	// Used for ordering of embedded modules within a layout
	private $embedId;

	// Whether the loop should continue through the current layout
	protected $recursive;

	// Data sent by forms defined within the system, used by IH module extensions
	protected $formData;

	// Whether to run init() automatically
	protected $autoInit;

	// Whether to run init() automatically
	protected $autoFinalize;


	public static function hasModel()
	{
		return static::$hasModel;
	}


	final public function __construct(array $params = [])
	{
		$this->reflector = new ReflectionClass($this);
		$this->class = $this->reflector->getShortName();
		
		$this->name = strtolower($this->class);
		if (substr($this->name, -4) == 'base') {
			$this->name = substr($this->name, 0, -4);
		};
		
		$this->error = [
			'errorOccured'=>false,
			'message'=>''
		];
		
		$this->triggerAction = $params['triggerAction'] ?? $this->triggerAction ?? false;
		$this->layout = $params['layout'] ?? Settings::get('defaultModuleLayout');
		$this->layoutVariant = $params['layoutVariant'] ?? Settings::get('defaultModuleLayoutVariant');
		$this->layoutProcessed = false;
		$this->layoutVariables = [];
		$this->layoutFilters = $params['layoutFilters'] ?? [];
		$this->layoutJsAutoLoad = $params['layoutJsAutoLoad'] ?? true;
		$this->layoutCssAutoLoad = $params['layoutCssAutoLoad'] ?? true;
		$this->embeddedModules = [];
		$this->embedId = 0;
		$this->parentModule = $params['parentModule'] ?? null;
		$this->recursive = $params['recursive'] ?? true;

		// Checking whether it is an addon
		$nameParts = explode('_', $this->name, 2);
		if (count($nameParts) > 1) {
			if (in_array($nameParts[0], array_keys(Config::get('moduleAddons')))) {
				$this->addonType = $nameParts[0];
			} else {
				$this->addonType = null;
			}
		} else {
			$this->addonType = null;
		}

		/*
		Code that should run every time the module is instantiated
		shall be put into its init() function
		The module addons will not run the init function by default, but you can
		override this via a constructor parameter, or by assigning a non-null value to it for the addon
		*/

		$this->autoInit = $params['autoInit'] ?? $this->autoInit ?? ($this->addonType === null);
		$this->autoFinalize = $params['autoFinalize'] ?? $this->autoFinalize ?? ($this->addonType === null);
		
		$this->params = $params;
		$this->params['id'] = $this->params['id'] ?? 0;

		/*
		For unique functionality on a request, use actions)
		If you want to use a model, create a class in the model.{modulename}.php file,
		and call the loadModel() in the controller's init() function
		To access URL parameters call loadPathParams() within the module class
		*/
		
		if ($this->autoInit) {
			$this->init();
		}

		/*
		Triggering an action if requested
		Default actions will only be triggered for the primary module matched by the router
		*/

		if ($this->triggerAction) {
			$action = Router::getRequestedAction();
			if ($action) {
				$this->triggerAction($action);
			}
		}

		if ($this->autoFinalize) {
			$this->finalize();
		}

	}


	public function __toString()
	{
		return $this->processLayout()->getLayoutHtml() ?? '';
	}


	protected function init(){}


	protected function finalize(){}


	protected function autoInit(?bool $trigger = null)
	{
		if ($trigger === null) {
			return $this->autoInit;
		}

		$this->autoInit = $trigger;
		return $this;
	}


	private function triggerAction(string $action)
	{
		$actionMethod = $action . 'Action';
		if (method_exists($this, $actionMethod)) {
			$this->$actionMethod();
			Debug::alert('Action ' . $action . ' for %' . $this->class . ' successfully triggered.', 'o');
		} else {
			Debug::alert('Action ' . $action . ' for %' . $this->class . ' could not be triggered.' , 'f');
		}
	}


	protected function autoFinalize(?bool $trigger = null)
	{
		if ($trigger === null) {
			return $this->autoFinalize;
		}

		$this->autoFinalize = $trigger;
		return $this;
	}


	protected function error($message = null)
	{
		if ($message === null) {
			return $this->error;
		}

		if ($message === false) {
			$this->error = ['errorOccured'=>false, 'message'=>''];
		} else {
			$this->error = ['errorOccured'=>true, 'message'=>$message];
			Debug::alert($message, 'f');
		}
		return $this;
	}


	protected function layout(?string $layout = null)
	{
		if ($layout === null) {
			return $this->layout;
		}
		
		$this->layout = $layout;
		return $this;
	}


	protected function layoutVariant(?string $variant = null)
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
	public function processLayout()
	{
		if ($this->error()['errorOccured']) {
			Debug::alert("Cannot process layout of %$this->name #" . $this->params['id']);
			$this->layoutHtml = null;
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
			// Extract the variabless to local namespace
			extract($this->layoutVariables);

			// Start output buffering
			ob_start();

			include($layout['layoutFile']);

			// Get the contents of the buffer
			$this->layoutHtml = ob_get_contents();

			// End buffering and discard
			ob_end_clean();
		}

		// CSS, JS autoload
		// Adding the module layout's assets to the head
		if (!empty($layout['layoutDir'])) {
			if ($this->layoutJsAutoLoad) {
				$this->jsAutoLoad($layout['layoutDir']);
			}
			if ($this->layoutCssAutoLoad) {
				$this->cssAutoLoad($layout['layoutDir']);
			}
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
						$embeddedModule = $this->loadModule($module['params']['name'], $module['params']);
						
						if ($embeddedModule !== false) {
							// The module has been successfully loaded
							$embedHtml = $embeddedModule->processLayout()->getLayoutHtml() ?? '';
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
		if ($variant === null) {
			$variant = $layout;
		}

		if (file_exists(DOMAIN_DIR . DS . 'layouts' . DS . $this->name . DS . $layout . DS . $variant . '.php')) {
			$layoutDir = DOMAIN_DIR . DS . 'layouts' . DS . $this->name . DS . $layout;
			$layoutFile = $layoutDir . DS . $variant . '.php';
		} elseif (file_exists(ENGINE_DIR . DS . 'layouts' . DS . $this->name . DS . $layout . DS . $variant . '.php')) {
			// Trying to fallback to the base module layout
			$layoutDir = ENGINE_DIR . DS . 'layouts' . DS . $this->name . DS . $layout;
			$layoutFile = $layoutDir . DS . $variant . '.php';
		} else {
			$layoutFile = null;
			$layoutDir = null;
			Debug::alert('Layout ' . $variant . ' for module %' . $this->name . ' not found.', 'f');
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
			if ($this->name != $params['name']
				|| !array_key_exists($params['name'] . '#' . $params['id'] , App::getRegisteredModules())) {
				
				// Adding the embedded module to the list
				$module = App::getActiveModules()->first(function($value, $key) use ($params){
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


	// Loads a module and registers it in the system (App)
	// Sets the parent module's name and ID for further use
	protected function loadModule(string $moduleName, array $params)
	{
		// Only active module classes can be instantiated
		if (App::getActiveModules('name')->contains($moduleName)) {
			$params['parentModule'] = $this;
			$moduleName = '\\Arembi\Xfw\\Module\\' . $moduleName;

			$module = new $moduleName($params);

			return $module;
		} else {
			Debug::alert('Embedded module %' . $moduleName . ' could not be found.', 'f');
			return false;
		}
	}


	// Adds the variable to the layout variables
	public function lv(string $var, $value)
	{
		$this->layoutVariables[$var] = $value;
		return $this;
	}


	protected function loadModel()
	{
		// Attempting to activate the module's model
		$moduleModel = $this->reflector->getShortName() . 'Model';

		// Module extensions cp and ih use the same model as their parent class
		$parts = explode('_', $moduleModel, 2);
		if (in_array(strtolower($parts[0]), array_keys(Config::get('moduleAddons')))) {
			$moduleModel = $parts[1];
		}
		// Adding namespace
		$moduleModel = '\\Arembi\\Xfw\\Module\\' . $moduleModel;

		// Instantiating and initialising the module's model
		if (class_exists($moduleModel)) {
			$this->model = new $moduleModel();
			if (method_exists($this->model, 'init')) {
				$this->model->init();
			}
		}
	}


	/* 
		Prints $value (in the layouts) after applying the layoutFilters
		If $value is passed as an array, it is assumed to contain the
		different language-variations
		Filters can be given as a single or an array of strings
		A module can impose the layout filters via the module params
	*/
	protected function print(string|array $value, ?array $mutators = null)
	{	
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
			$filterClass = "\\Arembi\\Xfw\\Filter\\$filter" . "LayoutFilter";
			if (class_exists($filterClass)) {
				$filter = new $filterClass();
			}
			$printedValue = $filter->filter($printedValue);
		}

		echo $printedValue;
	}


	protected function a(string $href, string|array $anchor = '', array $params = [])
	{
		$linkParams = [
			'href'=>$href,
			'anchor'=>$anchor,
			...$params
		];

		$this->embed('link', $linkParams);
	}


	protected function img(string $src, ?array $attributes = null)
	{
		$imageAttributes = [
			'src'=>$src,
			...$attributes
		];
		$this->embed('image', $imageAttributes);
	}


	public function getLayoutHtml()
	{
		return $this->layoutHtml;
	}


	public function jsAutoLoad(string $layoutDir)
	{
		if (is_dir($layoutDir . DS . 'js')) {
			$JSList = Misc\listFiles($layoutDir . DS . 'js', '/', FALSE, 'js');
			foreach ($JSList as &$JS) {
				$JS = str_replace(SITES_DIR . DS . DOMAIN , '', $JS);
			}
			unset($JS);
			Head::addJS($JSList);
		}
	}


	public function cssAutoLoad(string $layoutDir)
	{
		if (is_dir($layoutDir . DS . 'css')) {
			$cssList = Misc\listFiles($layoutDir . DS . 'css', '/', FALSE, 'css');
			foreach ($cssList as &$css) {
				$css = str_replace(SITES_DIR . DS . DOMAIN , '', $css);
			}
			unset($css);
			Head::addCSS($cssList);
		}
	}


	public function render()
	{
		echo $this->layoutHtml;
	}


	// Transfers the parameters set in the URI to the module params
	protected function loadPathParams()
	{
		$pathParams = Router::getPathParams();

		$pathParamOrder = Router::getMatchedRoutePpo() ?? App::getPathParamOrder($this->name) ?? [];

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


	// Returns the parent module
	public function parent()
	{
		return $this->parentModule;
	}


	// Returns the embedded modules
	public function children()
	{
		return $this->embeddedModules;
	}


	public function child(int $n)
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
