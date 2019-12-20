<?php
/*
Router tasks:
 	- load routes
	- load links
	- detect the environment
	- translate the path
 	- detect document layout
 	- detect primary module
 	- detect and set language
 */

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Misc;

abstract class Router {

	private static $model = null;

	// http, https
	protected static $protocol = '';

	// protocol + domain
	protected static $hostUrl = '';

	// equivalent to apache's %{REQUEST_URI}
	protected static $path = '';

	// The URL without the query string
	protected static $noQSUrl;

	// protocol + domain + path + query string
	protected static $fullUrl  = '';

	// parts of the path
	private static $pathNodes = []; // The segments of the path
	private static $pathMain = ''; // The path without the query string
	private static $pathQueryString = ''; // The query string

	// The parameters given to the primary modules via the path (query string not included)
	private static $pathParams = [];

	// The registered domains, will not be loaded automatically
	private static $domains = [];

	// Custom redirects from the DB
	private static $redirects = [];

	// Saved links in the system
	public static $links = [];

	// Defined routes in the system
	private static $routes = [];

	// Available primary modules
	private static $primaryModuleRoutes = [];

	// Available backend modules
	private static $backendModuleRoutes = [];

	// hit404 shall be set to true if a 404 error occures
	// It should prevent further scripts from execution
	private static $hit404 = false;

	// The matched routes record
	private static $matchedRoute = [];

	// The number of the page used by the pagination
	private static $pageNumber;

	// Collection of URL parameters for the page number
	private static $paginationParams;

	// The default page numbering URL parameter on the domain
	// eg. page, oldal, seite
	private static $paginationParam;

	// Global clones
	public static $GET     = [];
	public static $POST    = [];
	public static $REQUEST = [];
	public static $SERVER  = [];

	// TODO: file upload
	public static $FILES = [];



	// Initialization
	public static function init()
	{
		// Instantiating the model
		self::$model = new RouterModel();

		if(!self::getDomains()){
			App::hcf();
		}
	}


	/*
	 * Sets up the system environment based on configuration and the request
	 * Assigns values to the Router URL variables
	 * */
	public static function getEnvironment()
	{
		/*
		 * Making a working copy of the request globals
		 * */
		self::$GET     = $_GET;
		self::$POST    = $_POST;
		self::$REQUEST = $_REQUEST;
		self::$SERVER  = $_SERVER;

		// Storing the URI
		$uri = urldecode(self::$SERVER['REQUEST_URI']);

		// Cutting off the query string
		$uriQ = explode('?', $uri, 2);

		/*
		Checking whether it is a development environment or not
		(assuming that dev is mainly performed on localhost)
		*/

		if (in_array(self::$SERVER['REMOTE_ADDR'], Config::_('localhostIP'))
			|| strpos(self::$SERVER['REMOTE_ADDR'], '192.168') !== false) {
			$isLocalhost = true;
			$uriParts = explode('/', $uriQ[0]);

			/*
			 * $uriParts[1] is the web root directory
			 * $uriParts[2] has to be the domain
			 *
			 * for instance localhost/myDir/example.com
			 *   the web root is myDir
			 *   the domain is example.com
			 *
			 * INSTALL MEMO
			 * 	You have to put your app in a subdirectory in your
			 *  localhost folder, for example: /var/www/html/myDir
			 *
			 * */
			if (!empty($uriParts[2])) {
				$webRoot = $uriParts[1];
				$domain = $uriParts[2];
				self::$pathMain = '/' . implode('/', array_slice($uriParts, 3));
				self::$path = self::$pathMain . (isset($uriQ[1]) ? '?' . $uriQ[1] : '');
			} else {
				Debug::alert('Loading root page contents.', 'o');
				App::hcf(file_get_contents(ENGINE . DS . 'welcome.html'));
			}
		} else {
			$webRoot = '';
			$domain = self::$SERVER['HTTP_HOST'];
			$isLocalhost = false;

			self::$pathMain = $uriQ[0];
			self::$path = $uri;
		}

		// Defining constants
		define('WEB_ROOT', $webRoot);
		define('DOMAIN', $domain);
		define('DOMAIN_ID', self::getDomainID($domain));
		define('IS_LOCALHOST', $isLocalhost);
		define('HOST_ROOT', (IS_LOCALHOST ? self::$SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '') . DOMAIN);
		define('DOMAIN_DIRECTORY', SITES . DS . DOMAIN);

		// Retrieveing domain data if registered, otherwise false
		$domainRecord = self::getDomainByID(DOMAIN_ID);

		// If the domain is not registered, we return a 404 error
		if (!$domainRecord) {
			Debug::alert('The domain ' . $domain . ' has not been registered in the system.', 'f');
			App::hcf(file_get_contents(ENGINE . DS . '404.html'));
		}

		// Identifying the protocol
		self::$protocol = IS_LOCALHOST ? 'http://' : $domainRecord['protocol'];

		$requestProtocol =
			(!empty(self::$SERVER['HTTPS'])
			&& self::$SERVER['HTTPS'] != 'off'
			|| self::$SERVER['SERVER_PORT'] == 443)
			? "https://"
			: "http://";

		// Redirecting bad protocol requests to the right ones
		if (self::$protocol != $requestProtocol) {
			self::redirect(self::$protocol . HOST_ROOT . self::$path, 307);
		}

		// Storing request URLs
		self::$hostUrl = self::$protocol . HOST_ROOT;
		self::$fullUrl = self::$hostUrl . self::$path;

		// Constructing path variables
		self::$noQSUrl = self::$hostUrl . self::$pathMain;

		self::$pathNodes = explode('/', substr(self::$pathMain, 1)); // Initial '/' is removed
		if (isset($uriQ[1])) {
			parse_str($uriQ[1], self::$pathQueryString);
		}

		// Detecting AMP
		if (isset(self::$REQUEST['amp']) && self::$REQUEST['amp'] == 1) {
			$status = 'on';
		} else {
			$status = 'off';
		}
		App::AMP($status);
		Debug::alert('[AMP] status: ' . $status . '.');
	}


	public static function loadData()
	{
		// Loading previously saved links
		self::$links = self::$model->getSystemLinks();

		// Loading stored redirects
		self::$redirects = self::$model->getRedirects();

		// Loading the available routes from the database
		self::$routes = self::$model->getAvailableRoutes();
	}


	/*
	automatic redirects,
	trailing slashes,
	language markers in URLs
	Parses the path */
	public static function parseRoute()
	{
		/*
		Directly served files (CSS, JS, PDF, etc)
		*/
		self::serveFiles();


		/*
		Handling user input (f.i. forms)
		*/
		self::handleInput();


		/*
		Check whether the given URL needs to be redirected
		The script will be stopped at this point, if a redirect has been set to the current route
		and a new request will be made immediately
		*/
		// TODO self::autoRedirect();


		/*
		TRAILING SLASHES
		all 3 types are supported:
		every URL ends with a slash
		none of them ends with a slash
		both are possible
		*/

		if (Settings::_('URLTrailingSlash') == 'remove') {
			if (self::$pathMain != '/' && substr(self::$pathMain, -1) == '/') {
				self::redirect(substr(self::$fullUrl, 0, -1));
			}
		} elseif (Settings::_('URLTrailingSlash') == 'force') {
			if (substr(self::$pathMain, -1) != '/') {
				self::redirect(self::$fullUrl . '/');
			}
		}


		/*
		 * LANGUAGE MARKERS
		 * The indicator in the URL can only be the first segment of the path
		 * for instance example.com/en
		 * */

		/*
		 * First the path nodes and the main path need to be copied,
		 * so the modifications during the parsing will not overwrite them
		 * */
		$pathNodes = self::$pathNodes;
		$pathMain  = self::$pathMain;

		/*
		 * If the site is multilingual, we have to detect which language
		 * the user wants to load, doing that by calling matchLanguage()
		 * */
		if (Settings::read('multiLang') == 'true') {
			$lang = self::matchLanguage($pathNodes, $pathMain);
		} else {
			$lang = Settings::read('defaultLanguage');
		}

		App::setLang($lang);
		$_SESSION['lang'] = $lang;


		if (!self::$hit404 && !self::$routes) {
			return [
				'primary'=>'unauthorized',
				'action'=>'default',
				'documentLayout'=>
				Settings::_('defaultDocumentLayout')
			];
		}

		foreach (self::$routes as $r) {
			if ($r->moduleClass == 'b') {
				self::$backendModuleRoutes[$r->ID] = $r;
			} else {
				// It has to be a primary module, because secondary modules are not associated with URLs
				self::$primaryModuleRoutes[$r->ID] = $r;
			}
		}
		unset($r);

		// Setting up pagination
		self::$paginationParams = Settings::_('paginationParam');
		self::$paginationParam = self::$paginationParams[$lang];
		self::$pageNumber = !empty(self::$GET[self::$paginationParam]) ? self::$GET[self::$paginationParam] : null;

		/*
		The APP needs only the DOCUMENT LAYOUT and the PRIMARY MOODULE and
		the primary module's ACTION to load
		matchRoute will do just that
		*/

		return self::matchRoute($pathNodes, $pathMain);
	}


	// Returns the corresponding document layout, the primary module and its action to execute, based on th URI
	public static function matchRoute($pathNodes, $pathMain)
	{
		// In case a 404 error previously occured
		if (self::$hit404) {
			$match['primary'] = 'fourohfour';
			$match['action'] = 'default';
			$match['documentLayout'] = Settings::_('defaultDocumentLayout');
			return $match;
		}

		$match = [];

		/*
		Processing the module part
		If the site is multilingual, then at this point, the language marker in the URL has been already removed
		*/

		// The root route is a special URI, works rather different form the 'normal' ones
		if ($pathMain == '/' || $pathMain == '') {
			/*
			We cannot set module parameters via SEF URLs for the primary module at the root route,
			everything for it has to be set somewhere else (i.e. default vaules in the sys_config table
			This is why: consider the URL mysite.com/contact-me
				/ is a blog module
				/contact-me should be a static page
				If the primary module for /contact-me hasn't been found
				it should be a 404 and not a blog with the first URL parameter of contact-me
			*/
			$rootID = self::getIDByRoute('/');

			if ($rootID !== false) {
				$match['documentLayout'] = self::$primaryModuleRoutes[$rootID]->moduleConfig['documentLayout']
					?? Settings::_('defaultDocumentLayout');
				$match['primary'] = self::$primaryModuleRoutes[$rootID]->moduleName;
				$match['action'] = self::$primaryModuleRoutes[$rootID]->moduleConfig['action']
					?? null;

				self::$matchedRoute = self::$primaryModuleRoutes[$rootID];
			} else {
				$match['primary'] = 'fourohfour';
				$match['action'] = 'default';
				$match['documentLayout'] = Settings::_('defaultDocumentLayout');
				return $match;
			}
		} else {
			/*
			The match candidate is stored as an array: [routeRecord, accuracy]
			we loop through the primaryModuleRoutes, and log every matched route
			afterwards we select the one with the highest accuracy
			For instance:
				the requested route is /static/pages/page1
				and we have
				/static, /static/pages and /static/pages/page1
				with accuracy 1,2,3 respectively
				the system will select /static/pages/page1 being the most accurate

			For instance, we can use /mystuff URI as a static page, but can use the /mystuff/diary as a blog page
			otherwise it could be interpreted as a static page at /mystuff with the first parameter: diary
			*/
			$bestMatch = [
				'match' => false,
				'accuracy' => 0
				];

			foreach (self::$primaryModuleRoutes as $ID => $r) {
				if (isset($r->path[App::getLang()])) { // the root won't be a contender because of this
					if (strpos($pathMain, $r->path[App::getLang()]) === 0 ) {
						/*
						The whole segment has to match, so we need to check the following
						character after the candidate route
						Example where things could go wrong:
						/pages/page1
						/pages/page10
						here the route /pages/page1 would match both paths
						*/

						$length = strlen($r->path[App::getLang()]);
						$nextChar = substr($pathMain, $length, 1);
						$accuracy = substr_count($r->path[App::getLang()], '/');

						if (($nextChar == '/' || !$nextChar) && $accuracy > $bestMatch['accuracy']) {
							$bestMatch = [
								'match' => $r,
								'accuracy' => $accuracy
								];
						}
					}
				}
			}

			// The primary module has been found, the remainder of the path are the path parameters
			if ($bestMatch['match'] !== false) {
				// +1 for the array indexing and another +1 for the starting slash
				self::$pathParams = explode('/', substr($pathMain, strlen($bestMatch['match']->path[App::getLang()]) + 1 ));
				self::$matchedRoute = $bestMatch['match'];
				$match['primary'] = $bestMatch['match']->moduleName;
				$match['action'] = $bestMatch['match']->moduleConfig['action'] ?? null;
				$match['documentLayout'] = empty($bestMatch['match']->moduleConfig['documentLayout'])
					? Settings::_('defaultDocumentLayout')
					: $bestMatch['match']->moduleConfig['documentLayout'];
			} else {
				$match['documentLayout'] = Settings::_('defaultDocumentLayout');
			}
		}

		if (!isset($match['primary'])) {
			// Primary module match not found
			$match['primary'] = 'fourohfour';
			$match['action'] = 'default';
			$match['documentLayout'] = Settings::_('defaultDocumentLayout');
			Debug::alert('Primary module not found.', 'f');
		} else {
			/*
			Check whether the current user has the proper clearance level to access this route
			If not, rewrite the primary module to unauthorized
			*/
			if (!$_SESSION['user']->allowedHere()) {
				$match['primary'] = 'unauthorized';
				//$match['documentLayout'] = Settings::_('defaultDocumentLayout');
			}
			Debug::alert('Primary module found: %' . $match['primary'], 'o');

		}

		// When AMP is enabled, it will override the documents layout
		if (App::AMP() == 'on') {
			AMP::init();
			$match['documentLayout'] = 'amp';
		}

		return $match;
	}


	/*
	 * Determines the language that should be used by the system
	 * */
	private static function matchLanguage(&$pathNodes, &$pathMain)
	{
		// The available languages are stored in an array for each language,
		// beginning with the language marker used in this system (f.i. "en"), followed by other aliases,
		// (f.i. "en-GB")

		$langSetInURL = false;

		// Searching through the available languages
		$langIndex = 0;
		$avLangs = Settings::_('availableLanguages');
		$l = count($avLangs);
		while (!$langSetInURL && $langIndex < $l) {
			if ($pathNodes[0] == $avLangs[$langIndex][0]) {
				$langSetInURL = true;
			} else {
				$langIndex++;
			}
		}

		if ($langSetInURL) {
			// The language was set in the URL, it will override possible previous values
			$lang = $pathNodes[0];
			Debug::alert('Language detected: ' . $lang . ' (via URL).', 'o');
			// The language node wont be needed again
			$pathMain = substr($pathMain, strlen($pathNodes[0]) + 1); // Removing the language segment from the path to process
			array_shift($pathNodes);
		} else {
			/*
			The language was not set in the URL
			First we are looking for previously set values
			*/
			if (!(isset($_SESSION['lang']) && App::getLang())) {
				/*
				Trying to use the client's preferred languages whether one of them is supported by the system
				*/
				$clientLangs = explode(',' , self::$SERVER['HTTP_ACCEPT_LANGUAGE']);
				foreach ($clientLangs as &$value) {
					$value = explode(';' , $value);
					$value = $value[0]; // Throwing away the weight of the language (q=0.8 parts)
				}
				unset($value);

				$prefLangFound = false;
				$i = 0;
				while (!$prefLangFound && $i < $l) {
					$j = 0;
					$k = count($avLangs[$j]);
					while (!$prefLangFound && $j < $k) {
						if (in_array($clientLangs[$i], $avLangs[$j])) {
							$prefLangFound = true;
							// Preferred language found
							$lang = $avLangs[$j][0];
							Debug::alert('Language detected: ' . $lang . ' (via HTTP_ACCEPT_LANGUAGE).', 'o');
						} else {
							$j++;
						}
					}
					$i++;
				}

				// If no preferred language is supported, using system default. Sorry...
				if (!$prefLangFound) {
					$lang = Settings::read('defaultLanguage');
					Debug::alert('Language not detected, using default: ' . $lang, 'n');
				}
			} else {
				$lang = App::getLang();
				Debug::alert('Using language: ' . $lang, 'n');
			}

			// Adding the language marker to the URL and redirecting
			$to = self::$hostUrl . DS . $lang . self::$path;
			self::redirect($to, 302);

		}

		return $lang;
	}


	private static function serveFiles()
	{
		// Files are identified by the file extensions
		$extension = Misc\getFileExtension(self::$pathMain);

		$allowedExtensionsByDefault = Config::_('fileTypesServed');
		$allowedExtensionsOnSite = Settings::_('fileTypesServed');

		if (is_array($allowedExtensionsOnSite)) {
			$allowedExtensions = array_merge($allowedExtensionsByDefault, $allowedExtensionsOnSite);
		} else {
			$allowedExtensions = $allowedExtensionsByDefault;
		}

		if ($extension !== false && in_array($extension, $allowedExtensions)) {
			$file = DOMAIN_DIRECTORY . DS . self::$pathMain;
			if (file_exists($file)) {
				$mime = Misc\getMimeType($file);
				if (!$mime) {
					// Fallback to plain text if the MIME has not been found
					$mime = 'text/plain';
				}
				header('Content-Type: ' . $mime);
				readfile($file);
				exit;
			} else {
				App::hcf('Requested file could not be found.');
			}
		}
	}


	private static function handleInput()
	{
		// The forms have to identify themselves with their formID
		if (!empty(self::$REQUEST['formID'])) {
			// If the form is registered in the database, its ID has to be sent.
			// So we have to get the form processing function from the database.
			$result = Input_Handler::processForm(self::$REQUEST['formID']);
		} elseif (!empty(Router::$REQUEST['handlerModule']) && !empty(Router::$REQUEST['handlerMethod'])) {
			$result = Input_Handler::processStandard(Router::$REQUEST['handlerModule'], Router::$REQUEST['handlerMethod']);
		} else {
			return false;
		}

		// Case everything went fine
		if ($result['status'][0] == 'OK') {
			if (isset($result['status'][1])) {
				Debug::alert('Form processing result: ' . $result['status'][1], 'o');
			}
		} else {
			// Two types of error can occur: either theres a problem with the form
			// or the controller reported an error
			if (!empty($result['ihError'])) {
				Debug::alert('Error while handling form: ' . $result['ihError'], 'f');
			} else {
				Debug::alert('Form processing result: ' . $result['status'][1], 'f');
			}
		}

		if (isset($result['status']['data'])) {
			Input_Handler::setProcessResult($result['status']['data']);
		}
	}


	public static function getIDByRoute($path, $type = 'all')
	{
		if ($type == 'all') {
			$routeCollection = self::$routes;
		} elseif ($type == "primary") {
			$routeCollection = self::$primaryModuleRoutes;
		} elseif ($type == "backend") {
			$routeCollection = self::$backendModuleRoutes;
		}

		foreach ($routeCollection as $r) {
			foreach ($r->path as $rlang => $rroute) {
				if ($rroute == $path) {
					return $r->ID;
				}
			}
		}
		return false;
	}


	public static function redirect($to = '', $type = '301')
	{
		if (!$to) {
			$to = self::$fullUrl;
		}

		// Preventing permanent redirects in development environment
		if (APP_ENV == 'dev') {
			$type = 302;
		}

		switch ($type) {
			case '301':
				header('HTTP/1.1 301 Moved Permanently');
				header('Location: ' . $to );
				break;
			case '302':
				header('HTTP/1.1 302 Found');
				header('Location: ' . $to);
				break;
			case '307':
				header('HTTP/1.1 307 Moved Temporarily');
				header('Location: ' . $to);
				break;
			default:
				break;
		}
		// Stop further code execution
		exit;
	}


	// This function loads the active redirects from the database and redirects if the requested URI has been matched
	public static function autoRedirect()
	{
		foreach (self::$redirects as $redirect) {
			if (preg_match('/' . $redirect['rule'] . '/', substr(self::$path, 1))) {
				self::redirect($redirect['destination'], $redirect['type']);
			}
		}
	}


	/*
	Function to set the response to a HTTP 404 error code
	@param documentLayout can be used to set different 404 error page layouts
	directly from the controllers
	*/
	public static function hit404()
	{
		header('HTTP/1.1 404 Not Found');
		self::$hit404 = true;
	}


	public static function getPathParams()
	{
		return self::$pathParams;
	}


	public static function getPaginationParams()
	{
		return self::$paginationParams;
	}


	public static function getPaginationParam()
	{
		return self::$paginationParam;
	}


	// Returns the domain records registered in the system
	// Loads them first if it has not been done before
	public static function getDomains()
	{
		if (empty(self::$domains)) {
			self::$domains = self::$model->getDomains();
		}

		return self::$domains ?? false;
	}


	// Returns the domain name for the given ID, or false if it can't be found
	public static function getDomainByID($id)
	{
		return self::$domains[$id] ?? false;
	}


	// Returns the ID of the domain, or false if it can't be found
	public static function getDomainID($domain)
	{
		$result = Misc\md_array_lookup_key(self::$domains, 'domain', $domain);

		return $result;
	}


	public static function getRouteRecordByID($routeID)
	{
		$i = 0;
		$l = count(self::$routes);
		$route = null;

		while ($i < $l && $route === null) {
			if (self::$routes[$i]->ID == $routeID) {
				$route = self::$routes[$i];
			} else {
				$i++;
			}
		}

		if ($route === null) {
			$route = self::$model->getRouteByID($routeID);
		}

		return $route ?? false;
	}


	// The getRouteByID function is returns the actual route (/some/fancy/url) for the given route ID
	public static function getRouteByID($routeID, $lang = 'sys')
	{
		if ($lang == 'sys') {
			$lang = App::getLang();
		}

		$i = 0;
		$l = count(self::$routes);
		$route = null;

		while ($i < $l && $route === null) {
			if (self::$routes[$i]->ID == $routeID) {
				$route = self::$routes[$i];
			} else {
				$i++;
			}
		}

		if ($route !== null) {
			if ($route->path === '/') {
				$ret = $route->path;
			} elseif (isset($route->path[$lang])) {
				// Routes on the current domain have already been retrieved from the database
				$ret = $route->path[$lang];
			} else {
				// Route not yet loaded, fetching from database
				$ret = self::$model->getRouteByID($routeID, $lang);
				if ($ret) {
					$ret = $ret->path[$lang];
				}
			}
		} else {
			$ret = null;
		}

		return $ret;
	}


	public static function getProtocol()
	{
		return self::$protocol;
	}


	public static function getPath()
	{
		return self::$path;
	}


	public static function getQueryString()
	{
		return self::$pathQueryString;
	}


	public static function getFullUrl()
	{
		return self::$fullUrl;
	}


	public static function getNoQSUrl()
	{
		return self::$noQSUrl;
	}


	public static function getHostUrl()
	{
		return self::$hostUrl;
	}


	public static function getPageNumber()
	{
		return self::$pageNumber;
	}


	public static function getMatchedRoute()
	{
		return self::$matchedRoute ?? false;
	}


	public static function getMatchedRouteAction()
	{
		return self::$matchedRoute->moduleConfig['action'] ?? null;
	}


	public static function getMatchedRoutePpo()
	{
		return self::$matchedRoute->moduleConfig['ppo'] ?? self::$matchedRoute->modulePpo ?? false;
	}


	public static function getMatchedRouteID()
	{
		return self::$matchedRoute->ID ?? null;
	}


	public static function getRoutes($type = 'all')
	{
		switch ($type) {
			case 'primary':
				return self::$primaryModuleRoutes;
			case 'backend':
				return self::$backendModuleRoutes;
			default:
				return self::$routes;
		}
	}


	/*
	 * Retrieves the information necessary to construct the href of a link by
	 * its source, builds the href til the query string (hrefBase)
	 * returns the hrefBase and the query string as an array for further processing
	 * @param $source: supported sources are `link` and `route`
	 * @param $data: infrotmation used to generate the href
	 * 	- for the link it is the linkID
	 * 	- for a route it's the domain ID, route ID, the language, the path parameters
	 * 		and the query string
	 * */
	public static function href(string $source, $data)
	{
		if ($source == 'link') {
			// $data has to be the linkID
			if (!$data || !isset(self::$links[$data])) {
				Debug::alert('Link with ID ' . $data . ' not found.', 'f');
				return false;
			}

			// If the site is multilingual, we add the language marker to the URL
			if (Settings::_('multiLang')) {
				if (isset(self::$links[$data]['linkLang'])) {
					$lang = self::$links[$data]['linkLang'];
				} else {
					$lang = App::getLang();
				}

				$langMarker = DS . $lang;

				if (self::$links[$data]['path'] != '/') {
					if (isset(self::$links[$data]['path'][$lang])) {
						$route = self::$links[$data]['path'][$lang];
					} else {
						return false;
					}
				} else {
					$route = '/';
				}
			} else {
				$lang = App::getLang();
				$langMarker = '';
				if (self::$links[$data]['path'] != '/') {
					$route = self::$links[$data]['path'][Settings::_('defaultLanguage')];
				} else {
					$route = '/';
				}
			}

			// Assembling path parameters
			$pathParams = '';

			foreach (self::$links[$data]['ppo'] as $ID => $link) {
				$pathParams .= (isset(self::$links[$data]['pathParams'][$link]))
					? '/' . self::$links[$data]['pathParams'][$link]
					: '';
			}

			// The query string is stored in an array at this point
			if (!empty(self::$links[$data]['queryString'])) {
				$queryString = self::$links[$data]['queryString'];
			} else {
				$queryString = [];
			}

			$domain = self::$links[$data]['domain'];

			$href = self::$protocol
				. (IS_LOCALHOST ? self::$SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
				. $domain
				. $langMarker
				. $route
				. $pathParams;

		} elseif ($source == 'route') {
			/*
			 * href= "+route=19+lang=hu+pathParam1=abc+pathParam2=xyz"
			 * @param data
			 * 	the href converted to an array
			 * */

			if (empty($data['route'])) {
				Debug::alert('Could not build href for route #' . $data['route'] . ': parameters missing.', 'f');
				return false;
			}

			/*
			* If the language has not been set, we use the deafult language on the domain
			* */
			if (empty($data['lang']) || !Settings::_('multiLang')) {
				$lang = App::getLang();
			} else {
				$lang = $data['lang'];
				unset($data['lang']);
			}

			// Assembling the path
			// The remainder of the $data are the path parameters
			$pathParams = '';

			$record = self::getRouteRecordByID($data['route']);

			if (!$record) {
				Debug::alert('Could not build href for route #' . $data['route'] . ': route missing.', 'f');
				return false;
			}

			if ($record->path !== '/') {
				$route = $record->path[$lang];
			} else {
				$route = $record->path;
			}

			$ppo = $record->moduleConfig['ppo'] ?? $record->modulePpo;

			if (!empty($ppo)) {
				foreach ($ppo as $param) {
					$pathParams .= isset($data[$param])
						? '/' . $data[$param]
						: '';
				}
			}

			$langMarker = Settings::_('multiLang') ? (DS . $lang) : '';

			// A queryString array has to be returned, but we do not use when
			// builing hrefs based on routes, so an empty array is returned
			$queryString = [];

			$domain = self::getDomainByID($record->domainID);

			$href = (IS_LOCALHOST ? 'http://' : self::$protocol)
				. (IS_LOCALHOST ? self::$SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
				. $domain['domain']
				. $langMarker
				. $route
				. $pathParams;
		} else {
			$href = $data;
		}

		$ret = [
			'lang' => $lang,
			'base' => $href,
			'queryString' => $queryString
		];

		return $ret;

	}

}
