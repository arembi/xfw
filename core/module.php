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

Module options
	Can be passed on as constructor parameters
	parentModule->name f.i.: static_page
	parentModule->id f.i.: 19 means the static_page with the ID 19 (not the sys_module ID)

Recursion
	By default, the system will go through every layout, and load the moduleVars and embedded modules.
	However, if you, for instance need only the layout of the module,
	you can do it by setting the $options['recursive'] to 'no'.

Actions
	Actions are methods of module classes. They can be triggered by HTTP requests.

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
remember to set the options['id']. (If not, it will get one in its main())

!!! DO NOT FORGET !!!
Each module class has to call the loadModel() and the loadPathParams() functions
in their main() function in order to be able to use the database and have access
to the path parameters.
Had to move those 2 functions out of the constructor, because we need to use
them before the parent's constructor is called.

The module addons inherit the path parameters (and path parameter order)
from their main modules.
*/

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Misc;
use Arembi\Xfw\Module\Head;

abstract class ModuleCore {
	// Whether the autoloader should look for a model
	protected static $hasModel;

	// Registration ID, used by the system to identify instantiated modules
	protected $rId;

	// The module's name
	protected $name;

	// Holds the module addon type, or false if it is not an addon
	protected $addonType;

	// Reference to the module which embedded this
	protected $parentModule;

	// The module's model
	protected $model;

	// The HTML output for the module after processing its layout
	protected $layoutHTML;

	// Shows whether the module's layout has already been processed
	protected $layoutProcessed;

	// Constructor options
	protected $options;

	// Variables that can be accessed in layouts
	protected $layoutVariables;

	// The array of embedded modules in the layout
	protected $embeddedModules;

	protected $reflector; // instance of ReflectionClass

	// embedID is used for ordering of embedded modules within a layout
	private $embedId;

	// Whether the loop should continue through the current layout
	protected $recursive;

	// data sent by forms defined within the system, used by IH module extensions
	protected $formData;


	final public function __construct(array $options = [])
	{
		$this->name = '';
		$this->layoutProcessed = false;
		$this->options = [];
		$this->layoutVariables = [];
		$this->embeddedModules = [];
		$this->embedId = 0;

		$this->reflector = new \ReflectionClass($this);
		$class = $this->reflector->getShortName();
		$this->name = strtolower($class);

		if (substr($this->name, -4) == 'base') {
			$this->name = substr($this->name, 0, -4);
		}

		// Checking whether it is an addon
		$parts = explode('_', $this->name, 2);

		if (count($parts) > 1) {
			if (in_array($parts[0], array_keys(Config::get('moduleAddons')))) {
				$this->addonType = $parts[0];
			} else {
				$this->addonType = null;
			}
		} else {
			$this->addonType = null;
		}

		// If the name has not been set, we will use the module class name
		if (empty($options['name'])) {
			$options['name'] = $this->name;
		}

		$this->parentModule = $options['parentModule'] ?? null;

		// Layout setup
		$this->options['layout'] = Settings::get('defaultModuleLayout');

		/*
		Code that should run every time the module is instantiated
		shall be put into its main() function

		The module addons will not run the main function by default

		For unique functionality, use actions

		If you want to use a model, create a class in the model.modulename.php file,
		and call the loadModel() in the controllers main() function

		To access URL parameters call the loadPathParams()
		*/

		if ($this->addonType === null && method_exists($this, 'main')) {
			$mainResult = $this->main($options);
		}

		$this->options = array_merge($this->options, $options);

		// If not set, ID will be 0
		if (!isset($this->options['id'])) {
			$this->options['id'] = 0;
		}

		/*
			* Triggering an action if requested
			* Default actions will only be triggered for the primary module matched by the router
			* */

		if (isset($this->options['triggerAction']) && $this->options['triggerAction']) {
			// actions set via GET will override default actions
			if (isset(Router::$GET['_action'])) {
				$action = Router::$GET['_action'];
			} else {
				$action = Router::getMatchedRouteAction();
			}

			if ($action) {
				$actionMethod = $action . 'Action';
				if (method_exists($this, $actionMethod)) {
					$this->$actionMethod();
					Debug::alert('Action ' . $action . ' for %' . $class . ' successfully triggered.', 'o');
				} else {
					Debug::alert('Action ' . $action . ' for %' . $class . ' could not be triggered.' , 'f');
				}
			} else {
				Debug::alert('No action was triggered for %' . $class . '.');
			}
		}
		$this->recursive = !isset($this->options['recursive']) || $this->options['recursive'] != "no";

		if (isset($mainResult) && $mainResult !== false) {
			Debug::alert('Error during execution of main() at module %' . $this->name . '#' . $this->options['id'], 'e');
		}

	}


	public static function hasModel()
	{
		return static::$hasModel;
	}


	/*
	* The layout processing covers the followings:
	* - put the layoutVariables into the layout
	* - if recursive, load embedded modules
	* */

	public function processLayout()
	{
		// Register the module in the system
		$this->rId = App::registerModule($this);

		// Load the module's layout
		$layout = $this->loadLayoutFile($this->options['layout']);

		// If the requested layout could not be loaded, we try to load the default
		if ($layout['layoutFile'] === null) {
			$layout = $this->loadLayoutFile(Settings::get('defaultModuleLayout'));
		}

		// Loading layout file
		if ($layout['layoutFile']) {
			// Extract the variabless to local namespace
			extract($this->layoutVariables);

			// Start output buffering
			ob_start();

			include($layout['layoutFile']);

			// Get the contents of the buffer
			$this->layoutHTML = ob_get_contents();

			// End buffering and discard
			ob_end_clean();
		}

		// CSS, JS autoload
		// Adding the module layout's assets to the head
		if (!empty($layout['layoutDir'])) {
			$this->JSAutoload($layout['layoutDir']);
			$this->CSSAutoload($layout['layoutDir']);
		}

		if (!empty($this->layoutHTML)) {
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
							$embedHTML = $embeddedModule->processLayout()->getLayoutHTML();
						} else {
							$embedHTML = '';
						}

						// Inserting the embedded HTML
						$this->layoutHTML = str_replace('{%' . $module['embedId'] . '%}', $embedHTML, $this->layoutHTML);
					}
				}
			}
		}

		$this->layoutProcessed = true;
		return $this;
	}


	protected function loadLayoutFile(string $layout)
	{
		if (file_exists(DOMAIN_DIR . DS . 'layouts' . DS . $this->options['name'] . DS . $layout . DS . $layout . '.php')) {
			$layoutDir = DOMAIN_DIR . DS . 'layouts' . DS . $this->options['name'] . DS . $layout;
			$layoutFile = $layoutDir . DS . $layout . '.php';
		} elseif (file_exists(ENGINE_DIR . DS . 'layouts' . DS . $this->options['name'] . DS . $layout . DS . $layout . '.php')) {
			// Trying to fallback to the base module layout
			$layoutDir = ENGINE_DIR . DS . 'layouts' . DS . $this->options['name'] . DS . $layout;
			$layoutFile = $layoutDir . DS . $layout . '.php';
		} else {
			$layoutFile = null;
			$layoutDir = null;
			Debug::alert('Layout ' . $layout . ' for module %' . $this->options['name'] . ' not found.', 'f');
		}

		return ['layoutFile' => $layoutFile, 'layoutDir' => $layoutDir];
	}


	protected function embed(string $name, array $params = [])
	{
		if (!$this->recursive){
			return false;
		}

		if (empty($name)) {
			// Case the module had not been set
			Debug::alert('Trying to embed an unspecified module, will be ignored', 'n');
		} else {
			$params['name'] = $name;
			// Case the module had been set
			// Preventing infinite loop: the embedded module with the same name as its parent has to have a different ID from the parent
			if ($this->options['name'] != $params['name']
				|| !array_key_exists($params['name'] . '#' . (isset($params['id']) ? $params['id'] : '0') , App::getRegisteredModules())) {
				// Adding the embedded module to the list
				$module = collect(App::getActiveModules())
					->first(function($value, $key) use ($params){
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
	protected function loadModule(string $moduleName, array $options)
	{
		// Only active module classes can be instantiated
		if (in_array($moduleName, App::getActiveModules('name'))) {
			if (!is_array($options)) {
				$options = [];
			}
			$options['parentModule'] = $this;
			$moduleName = '\\Arembi\Xfw\\Module\\' . $moduleName;

			$module = new $moduleName($options);

			return $module;
		} else {
			Debug::alert('Embedded module %' . $moduleName . ' could not be found.', 'f');
			return false;
		}
	}


	// Adds the variable to the layout variables
	public function lv(string $var, $value = null)
	{
		$this->layoutVariables[$var] = $value;
		return $this;
	}


	protected function loadModel()
	{
		// Attempting to activate the module's model
		$moduleModel = $this->reflector->getShortName() . 'Model';

		// Module extensions cp and fh use the same model as their parent class
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


	// Prints $str in the layouts after applying the layoutFilters
	// $layoutFilters can be given as a single or an array of strings
	// A module can impose the layout filters via the module options
	protected function print(string $str, $layoutFilter = null)
	{
		$filters = [];

		if (is_array($layoutFilter)) {
			$filters = $layoutFilter;
		} elseif (is_string($layoutFilter)) {
			$filters[] = $layoutFilter;
		}

		// Inheriting the filter demands from the module
		if (isset($this->options['layoutFilters'])) {
			$filters = array_merge($filters, $this->options['layoutFilters']);
		}

		foreach ($filters as $filter) {
			$filterClass = "\\Arembi\\Xfw\\Filter\\$filter";
			if (class_exists($filterClass)) {
				$filter = new $filterClass();
			}
			$str = $filter->filter($str);
		}

		echo $str;
	}


	protected function a(string $href, string $anchor = '', array $options = [])
	{
		$linkOptions = [
			'href'=>$href,
			'anchor'=>$anchor
		];

		$linkOptions = $linkOptions + $options;

		$this->embed('link', $linkOptions);
	}


	protected function img(array|string $attributes)
	{
		if (is_string($attributes)) {
			$this->embed('image', ['src'=>$attributes]);
		} else {
			$this->embed('image', $attributes);
		}
	}


	// Returns the given moduleVar
	public function getOption(string $var)
	{
		return $this->options[$var] ?? null;
	}


	public function getLayoutHTML()
	{
		if($this->layoutHTML){
			return $this->layoutHTML;
		} else {
			Debug::alert('Couldn\'t retrieve layout for ' . __CLASS__ . '.', 'f');
		}
	}


	public function JSAutoload(string $layoutDir)
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


	public function CSSAutoload(string $layoutDir)
	{
		if (is_dir($layoutDir . DS . 'css')) {
			$CSSList = Misc\listFiles($layoutDir . DS . 'css', '/', FALSE, 'css');
			foreach ($CSSList as &$CSS) {
				$CSS = str_replace(SITES_DIR . DS . DOMAIN , '', $CSS);
			}
			unset($CSS);
			Head::addCSS($CSSList);
		}
	}


	public function render()
	{
		echo $this->layoutHTML;
	}


	/*
	* Transfers the parameters set in the URI to the moduleVars
	* Pass the constructor parameter to it and everything will work
	*
	* Has to be called in every derived module class!
	* */
	protected function loadPathParams()
	{
		// Loading pathParams
		$pathParams = Router::getPathParams();

		$pathParamOrder = Router::getMatchedRoutePpo() ?? App::getPathParamOrder($this->name) ?? null;

		// Assigning the pathParams to the options
		// Path params WILL NOT OVERRIDE already existing module options
		if (!empty($pathParamOrder)) {
			foreach ($pathParamOrder as $key => $value) {
				if (!isset($this->options[$value])) {
					if (!empty($pathParams[$key])) {
						Debug::alert('[Router] the parameter \'' . $value . '\' was set to \'' . $pathParams[$key] . '\' via URL.', 'o');
						$this->options[$value] = $pathParams[$key];
					} else {
						Debug::alert('[Router] the parameter \'' . $value . '\' was not set via URL.', 'w');
						$this->options[$value] = null;
					}
				} else {
					Debug::alert('[Router] the parameter ' . $value . ' was already set, will not be overriden by the URL.');
				}
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


	// Returns the nth child
	public function child(int $n)
	{
		return $this->embeddedModules[$n] ?? null;
	}


	// Shows the moduleVars on the debug panel
	protected function moduleInfo()
	{
		$optInfo = [
			'debugTitle' => 'Constructor options of %' . $this->options['name'] . '#' . $this->options['id']
		];

		$options = $this->options;

		foreach ($options as $key => &$value) {
			$value = serialize($value);
		}
		unset($value);

		$optInfo = array_merge($optInfo, $options);
		Debug::alert($optInfo, 'i');

		$lvInfo = [
			'debugTitle' => 'Layout variables of %' . $this->options['name'] . '#' . $this->options['id']
			];

		$layoutVariables = $this->layoutVariables;

		foreach ($layoutVariables as $key => &$value) {
			$value = serialize($value);
		}
		unset($value);

		$lvInfo = array_merge($lvInfo, $layoutVariables);
		Debug::alert($lvInfo, 'i');
	}


	public function getFormData()
	{
		return $this->formData;
	}


	public function setFormData($data)
	{
		$this->formData = $data;
	}


	public function __destruct(){}


}
