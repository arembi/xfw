<?php
/*
Router tasks:
 	- load routes
	- load links
	- detect the environment
	- translate the path
 	- detect document layout & variant
 	- detect primary module
 	- detect and set language
 */

namespace Arembi\Xfw\Core;

use Arembi\Xfw\Misc;
use function Arembi\Xfw\Misc\getFileExtension;


abstract class Router {

	private static $model;

	// Whether the domain acts as an alias of another
	private static $aliasOf;

	// Http, https
	private static $protocol;

	// Protocol + domain
	private static $hostUrl;

	// Equivalent to Apache's %{REQUEST_URI}
	private static $path;

	// The URL without the query string
	private static $noQueryStringUrl;

	// Protocol + domain + path + query string
	private static $fullUrl;

	// Parts of the path
	private static $pathNodes; // The segments of the path
	private static $pathMain; // The path without the query string
	private static $queryString; // The query string

	private static $queryParameters;

	// The parameters given to the primary modules via the path (query string not included)
	private static $pathParams;

	// The registered domains, will not be loaded automatically
	private static $domains;

	// Custom redirects from the DB
	private static $redirects;

	// Saved links in the system
	private static $links;

	// Defined routes in the system
	private static $routes;

	// Available primary modules
	private static $primaryModuleRoutes;

	// Available backend modules
	private static $backendModuleRoutes;

	// hit404 shall be set to true if a 404 error occures
	// It should prevent further scripts from execution
	private static $hit404;

	// The matched route's record
	private static $matchedRoute;

	// The number of the page used by the pagination
	private static $pageNumber;

	// Collection of URL parameters for the page number
	private static $paginationParams;

	// The default page numbering URL parameter on the domain
	// eg. page, oldal, seite
	private static $paginationParam;

	// Global clones
	private static $GET;
	private static $POST;
	private static $REQUEST;

	private static $FILES;

	private static $inputInfo;
	private static $inputHandlerResult;

	
	public static function init()
	{
		self::$model = new RouterModel();	
		
		self::$GET = $_GET;
		self::$POST = $_POST;
		self::$REQUEST = $_REQUEST;
		self::$FILES = $_FILES;
		
		self::$aliasOf = null;
		
		self::$protocol = '';
		self::$hostUrl = '';
		self::$path = '';
		self::$noQueryStringUrl = '';
		self::$fullUrl = '';
		self::$pathNodes = [];
		self::$pathMain = '';
		self::$queryString = '';
		self::$queryParameters = [];
		self::$pathParams = [];
		self::$domains = [];
		self::$redirects = [];
		self::$links = [];
		self::$routes = [];
		self::$primaryModuleRoutes;
		self::$backendModuleRoutes = [];
		self::$hit404 = false;
		self::$pageNumber = null;
		
		self::$matchedRoute = null;
		self::$paginationParams = [];
		self::$paginationParam = '';
		
		self::loadDomains();

		self::$inputHandlerResult = null;
		self::$inputInfo = [
			'mode'=>'',
			'data'=>[]
		];
		
		if (empty(self::$domains)) {
			App::hcf('Could not retrieve domains.');
		}
	}


	public static function get(?string $key = null)
	{
		return $key === null ? self::$GET : self::$GET[$key] ?? null;
	}


	public static function post(?string $key = null)
	{
		return $key === null ? self::$POST : self::$POST[$key] ?? null;
	}


	public static function request(?string $key = null)
	{
		return $key === null ? self::$REQUEST : self::$REQUEST[$key] ?? null;
	}


	public static function files(?string $key = null)
	{
		return $key === null ? self::$FILES : self::$FILES[$key] ?? null;
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


	public static function getRequestedAction()
	{
		return self::request('_action') ?? self::getMatchedRouteAction() ?? null;
	}


	public static function getDomains()
	{
		return self::$domains ?? false;
	}


	// Returns the domain name for the given ID, or false if it can't be found
	public static function getDomainRecordById($id)
	{
		return self::$domains[$id] ?? false;
	}


	// Returns the ID of the domain, or false if it can't be found
	public static function getDomainId(string $domain)
	{
		return Misc\md_array_lookup_key(self::$domains, 'domain', $domain);
	}


	public static function getRouteRecordById(int $routeId)
	{
		return self::$routes->first(fn($r) => $r->id == $routeId);
	}


	// Returns the actual route (/lang/some/fancy/url) for the given route ID
	public static function getRouteById(int $routeId, string $lang = 'sys')
	{
		$ret = null;

		if ($lang == 'sys') {
			$lang = App::getLang();
		}

		$route = self::getRouteRecordById($routeId);

		if ($route !== null) {
			if ($route->path === '/') {
				$ret = $route->path;
			} elseif (isset($route->path[$lang])) {
				// Routes on the current domain have already been retrieved from the database
				$ret = $route->path[$lang];
			} else {
				// Route not yet loaded, fetching from database
				$ret = self::$model->getRouteById($routeId, $lang);
				if ($ret) {
					$ret = $ret->path[$lang];
				}
			}
		}

		return $ret;
	}


	public static function getAliasOf()
	{
		return self::$aliasOf;
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
		return self::$queryString;
	}


	public static function getFullUrl()
	{
		return self::$fullUrl;
	}


	public static function getNoQueryStringUrl()
	{
		return self::$noQueryStringUrl;
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
		return self::$matchedRoute ?? null;
	}


	public static function getMatchedRouteAction()
	{
		return self::$matchedRoute->moduleConfig['action'] ?? null;
	}


	public static function getMatchedRoutePpo()
	{
		return self::$matchedRoute->moduleConfig['ppo'] ?? self::$matchedRoute->modulePpo ?? null;
	}


	public static function getMatchedRouteId()
	{
		return self::$matchedRoute->id ?? null;
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


	public static function getLinks()
	{
		return self::$links;
	}


	public static function getLinkById(int $linkId)
	{
		return self::$links[$linkId] ?? self::$model->getLinkById($linkId);
	}


	public static function getIdByRoute(string $path, string $type = 'all')
	{
		if ($type == 'all') {
			$routeCollection = self::$routes;
		} elseif ($type == "primary") {
			$routeCollection = self::$primaryModuleRoutes;
		} elseif ($type == "backend") {
			$routeCollection = self::$backendModuleRoutes;
		}

		foreach ($routeCollection as $r) {
			foreach ($r->path as $rroute) {
				if ($rroute == $path) {
					return $r->id;
				}
			}
		}
		return false;
	}
	

	public static function inputHandlerResult(?Input_Handler_Result $result = null)
	{
		if ($result === null) {
			return self::$inputHandlerResult;
		}

		self::$inputHandlerResult = $result;
	}


	public static function inputInfo(?array $info = null)
	{
		if ($info === null) {
			return self::$inputInfo;
		}

		self::$inputInfo = $info;
	}


	/*
	 * Sets up the system environment based on configuration and the request
	 * Assigns values to the Router URL variables
	 * */
	public static function getEnvironment()
	{
		$uri = '';
		$uriQ = [];
		$uriParts = [];
		$domain = '';
		$domainId = null;
		$domainRecord = null;
		$originalDomainId = null;
		$originalDomainRecord = null;
		$dataDomainId = null;
		$isLocalhost = self::amIOnLocalhost();
		$webRoot = '';
		$requestProtocol = '';
		
		// Storing the URI
		$uri = urldecode($_SERVER['REQUEST_URI']);

		// Cutting off the query string
		$uriQ = explode('?', $uri, 2);

		if (isset($uriQ[1])) {
			self::$queryString = $uriQ[1];
			parse_str(self::$queryString, self::$queryParameters);
		}

		/*
		Checking whether it is a development environment or not
		(assuming that dev is mainly performed on localhost)
		*/
		if ($isLocalhost) {
			$uriParts = explode('/', $uriQ[0]);

			/*
			 * $uriParts[1] is the web root directory
			 * $uriParts[2] has to be the domain
			 *
			 * for instance http://localhost/myDir/example.com
			 *   the web root is myDir
			 *   the domain is example.com
			 *
			 * */
			if (!empty($uriParts[2])) {
				$webRoot = $uriParts[1];
				$domain = $uriParts[2];
				self::$pathMain = '/' . implode('/', array_slice($uriParts, 3));
				self::$path = self::$pathMain . (isset($uriQ[1]) ? '?' . $uriQ[1] : '');
			} else {
				Debug::alert('Loading root page contents.', 'o');
				App::hcf(file_get_contents(ENGINE_DIR . DS . 'welcome.html'), false);
			}
		} else {
			$domain = $_SERVER['HTTP_HOST'];

			self::$pathMain = $uriQ[0];
			self::$path = $uri;
		}
		
		$domainId = self::getDomainId($domain);
		
		if ($domainId) {
			// Retrieveing domain data if registered, otherwise false
			$domainRecord = self::getDomainRecordById($domainId);
		}

		// If the domain is not registered, we return a 404 error
		if (!$domainRecord) {
			Debug::alert('The domain ' . $domain . ' has not been registered in the system.', 'f');
			App::hcf(file_get_contents(ENGINE_DIR . DS . '404.html'), false);
		}

		/* 
			ALIASES
			If a domain is marked as an alias of another via the domain settings, the alias will inherit
			all settings, i.e routes, content from the original domain
		*/
		$originalDomainId = $domainRecord['settings']['aliasOf'] ?? false;
		
		if ($originalDomainId) {
			$originalDomainRecord = self::getDomainRecordById($originalDomainId);
			
			if ($originalDomainRecord 
				&& isset($originalDomainRecord['settings']['aliases'])
				&& in_array($domainId, $originalDomainRecord['settings']['aliases'])) {
				self::$aliasOf = [
					'id'=>$originalDomainId,
					...$originalDomainRecord
				];
				Debug::alert('ALIAS MODE, ALTERING DATA MIGHT AFFECT OTHER DOMAINS!', 'w');
				Debug::alert('Domain identified as alias for ' . $originalDomainRecord['domain'], 'n');
			}
		}
		
		$dataDomainId = self::$aliasOf['id'] ?? $domainId;
		
		// Defining constants
		define('WEB_ROOT', $webRoot);
		define('DOMAIN', $domain);
		define('DATA_DOMAIN_ID', $dataDomainId);
		define('DOMAIN_ID', $domainId);
		define('IS_ALIAS', $domainId != $dataDomainId);
		define('IS_LOCALHOST', $isLocalhost);
		define('HOST_ROOT', (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '') . DOMAIN);
		define('DOMAIN_DIR', SITES_DIR . DS . DOMAIN);
		define('UPLOADS_DIR', DOMAIN_DIR . DS . 'uploads');
		
		self::$protocol = IS_LOCALHOST ? 'http://' : $domainRecord['protocol'];

		$requestProtocol =
			(!empty($_SERVER['HTTPS'])
			&& $_SERVER['HTTPS'] != 'off'
			|| $_SERVER['SERVER_PORT'] == 443)
			? "https://"
			: "http://";

		if (self::$protocol != $requestProtocol) {
			self::redirect(self::$protocol . HOST_ROOT . self::$path, 307);
		}

		self::$hostUrl = self::$protocol . HOST_ROOT;
		self::$fullUrl = self::$hostUrl . self::$path;

		self::$noQueryStringUrl = self::$hostUrl . self::$pathMain;

		self::$pathNodes = explode('/', substr(self::$pathMain, 1)); // Initial '/' is removed
	}


	private static function amIOnLocalhost()
	{
		return (in_array($_SERVER['REMOTE_ADDR'], Config::get('localhostIP'))
			|| strpos($_SERVER['REMOTE_ADDR'], '192.168') !== false);
	}


	public static function loadData()
	{
		self::$links = self::$model->getSystemLinksByDomain();

		self::$redirects = self::$model->getRedirects();

		self::$routes = self::$model->getAvailableRoutes(DATA_DOMAIN_ID);
	}


	public static function parseRoute()
	{
		$inputResult = self::handleInput();
		
		if ($inputResult) {
			if ($inputResult->status() == Input_Handler::RESULT_SUCCESS) {
				Debug::alert('Input processing result: ' . $inputResult->message(), 'o');
				self::inputHandlerResult($inputResult);
			} elseif ($inputResult->status() == Input_Handler::RESULT_ERROR) {
				Debug::alert('Error while processing input: ' .  $inputResult->message(), 'f');
			}
		} else {
			Debug::alert('No input has been sent.');
		}
		
		self::serveFiles();

		/*
		TODO self::autoRedirect();
		*/

		/*
		TRAILING SLASHES
			force: every URL ends with a slash
			remove: none of them ends with a slash
			both
		*/
		if (Settings::get('URLTrailingSlash') == 'remove') {
			if (self::$pathMain != '/' && substr(self::$pathMain, -1) == '/') {
				self::redirect(substr(self::$fullUrl, 0, -1));
			}
		} elseif (Settings::get('URLTrailingSlash') == 'force') {
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
		 * the user wants to load, doing that by calling self::matchLanguage()
		 * */
		if (Settings::get('multiLang') == 'true') {
			$lang = self::matchLanguage($pathNodes, $pathMain);
		} else {
			$lang = Settings::get('defaultLanguage');
		}

		App::setLang($lang);
		$_SESSION['lang'] = $lang;

		if (!self::$hit404 && !self::$routes) {
			return [
				'primary'=>'unauthorized',
				'action'=>'default',
				'documentLayout'=>Settings::get('defaultDocumentLayout')
			];
		}

		foreach (self::$routes as $r) {
			if ($r->moduleClass == 'b') {
				self::$backendModuleRoutes[$r->id] = $r;
			} else {
				self::$primaryModuleRoutes[$r->id] = $r;
			}
		}
		unset($r);

		self::$paginationParams = Settings::get('paginationParam');
		self::$paginationParam = self::$paginationParams[$lang];
		self::$pageNumber = !empty(self::$GET[self::$paginationParam]) ? self::$GET[self::$paginationParam] : null;

		/*
		The APP needs only the DOCUMENT LAYOUT and the PRIMARY MOODULE and
		the primary module's ACTION to load
		matchRoute will do just that
		*/

		return self::matchRoute($pathMain);
	}


	// Returns the corresponding document layout, the primary module and its action to execute, based on th URI
	public static function matchRoute($pathMain)
	{
		if (self::$hit404) {
			return self :: assemble404Module();
		}

		$match = [];

		/*
		Processing the module part
		If the site is multilingual, then at this point, the language marker in the URL has been already removed
		*/

		// The root route is a special URI, works rather differently form the 'normal' ones
		if ($pathMain == '/' || $pathMain == '') {
			/*
			We cannot set module parameters via URLs for the primary module at the root route,
			everything for it has to be set somewhere else (i.e. default vaules in the sys_config table
			This is why: consider the URL mysite.com/contact-me
				/ is a blog module
				/contact-me should be a static page
				If the primary module for /contact-me hasn't been found
				it should be a 404 and not a blog with the first URL parameter of contact-me
			*/
			$rootId = self::getIdByRoute('/');

			if ($rootId !== false) {
				$match['documentLayout'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['documentLayout']
					?? Settings::get('defaultDocumentLayout');
				$match['documentLayoutVariant'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['documentLayoutVariant']
					?? Settings::get('defaultDocumentLayoutVariant');
				$match['primary'] = self::$primaryModuleRoutes[$rootId]->moduleName;
				$match['action'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['action'] ?? null;
				$match['params'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['params'] ?? [];

				self::$matchedRoute = self::$primaryModuleRoutes[$rootId];
			} else {
				return self :: assemble404Module();
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

			foreach (self::$primaryModuleRoutes as $id => $r) {
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
				$match['primary'] = self::$matchedRoute->moduleName;
				$match['action'] = self::$matchedRoute->moduleConfig['action'] ?? null;
				$match['params'] = self::$matchedRoute->moduleConfig['params'] ?? [];
				$match['documentLayout'] = self::$matchedRoute->moduleConfig['documentLayout'] ?? Settings::get('defaultDocumentLayout');
				$match['documentLayoutVariant'] = self::$matchedRoute->moduleConfig['documentLayoutVariant'] ?? Settings::get('defaultDocumentLayoutVariant');
			} else {
				$match['documentLayout'] = Settings::get('defaultDocumentLayout');
				$match['documentLayoutVariant'] = Settings::get('defaultDocumentLayoutVariant');
			}
		}

		if (!isset($match['primary'])) {
			// Primary module match not found
			$match = self :: assemble404Module();
			Debug::alert('Primary module not found.', 'f');
		} else {
			/*
			Check whether the current user has the proper clearance level to access this route
			If not, rewrite the primary module to unauthorized
			*/
			if (!$_SESSION['user']->allowedHere()) {
				$match['primary'] = 'unauthorized';
			}
			Debug::alert('Primary module found: %' . $match['primary'] . '.', 'o');
		}

		return $match;
	}


	private static function matchLanguage(&$pathNodes, &$pathMain)
	{
		// The available languages are stored in an array for each language,
		// beginning with the language marker used in this system (f.i. "en"), followed by other aliases,
		// (f.i. "en-GB")

		$langSetInURL = false;

		// Searching through the available languages
		$langIndex = 0;
		$avLangs = Settings::get('availableLanguages');
		$l = count($avLangs);
		while (!$langSetInURL && $langIndex < $l) {
			if ($pathNodes[0] == $avLangs[$langIndex][0]) {
				$langSetInURL = true;
			} else {
				$langIndex++;
			}
		}

		if ($langSetInURL) {
			
			// The language was set in the URL, it might override previous values
			$lang = $pathNodes[0];
			Debug::alert('Language detected: ' . $lang . ' (via URL).', 'o');
			
			// The language node wont be needed again
			$pathMain = substr($pathMain, strlen($pathNodes[0]) + 1); // Removing the language segment from the path to process
			array_shift($pathNodes);
		} else {
			
			// The language was not set in the URL
			// First we are looking for previously set values
			if (!($_SESSION['lang'] && App::getLang())) {
				
				// Trying to use the client's preferred languages whether one of them is supported by the system
				$clientLangs = explode(',' , $_SERVER['HTTP_ACCEPT_LANGUAGE']);
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


	private static function handleInput()
	{
		$result = null;

		if (!empty(self::$REQUEST['formId'])) {
			$result = Input_Handler::processStoredForm(self::$REQUEST['formId']);

			self::$inputInfo = [
				'mode'=>'form',
				'data'=>[
					'id'=>'formId',
					'handlerModule'=>$result->handlerModule(),
					'handlerMethod'=>$result->handlerMethod()
				]
			];
		} elseif (!empty(self::$REQUEST['handlerModule']) && !empty(self::$REQUEST['handlerMethod'])) {
			$result = Input_Handler::processGenericRequest(self::$REQUEST['handlerModule'], self::$REQUEST['handlerMethod']);
			self::$inputInfo = [
				'mode'=>'generic',
				'data'=>[
					'handlerModule'=>$result->handlerModule(),
					'handlerMethod'=>$result->handlerMethod()
				]
			];
		}

		return $result;
	}


	private static function serveFiles()
	{
		// Files are identified by the file extensions
		$extension = getFileExtension(self::$pathMain);
		
		if ($extension !== false) {
			$allowedExtensionsByDefault = Config::get('fileTypesServed');
			$allowedExtensionsOnSite = Settings::get('fileTypesServed');

			if (is_array($allowedExtensionsOnSite)) {
				$allowedExtensions = array_merge($allowedExtensionsByDefault, $allowedExtensionsOnSite);
			} else {
				$allowedExtensions = $allowedExtensionsByDefault;
			}

			if (in_array($extension, $allowedExtensions)) {
				$fs = FS::getFilesystem('site');

				if ($fs->fileExists(self::$pathMain)) {
					$mime = $fs->mimeType(self::$pathMain) ?? 'text/plain';
					header('Content-Type: ' . $mime);
					echo $fs->read(self::$pathMain);
					exit;
				} else {
					App::hcf('Requested file could not be found.');
				}
			}
		}
	}


	private static function assemble404Module()
	{
		return [
			'primary'=>'fourohfour',
			'action'=>'default',
			'documentLayout'=>Settings::get('defaultDocumentLayout'),
			'documentLayoutVariant'=>Settings::get('defaultDocumentLayoutVariant'),
			'params'=>[]
		];
	}


	public static function redirect(string $to = '', int $statusCode = 301)
	{
		if (!$to) {
			$to = self::$fullUrl;
		}

		// Preventing permanent redirects in development environment
		if (IS_LOCALHOST) {
			$statusCode = 302;
		}

		header('Location: ' . $to, true, $statusCode);
		
		exit;
	}


	// TODO
	public static function autoRedirect(){}


	public static function hit404()
	{
		header('HTTP/1.1 404 Not Found');
		self::$hit404 = true;
	}


	public static function loadDomains()
	{
		self::$domains = self::$model->getDomains();
	}


	/*
	 * Retrieves the information necessary to construct a href of a link by
	 * its source, builds the href up to the query string (hrefBase)
	 * returns the hrefBase and the query string as an array for further processing
	 * @param $source: supported sources are `link` and `route`
	 * @param $data: infrotmation used to generate the href
	 * 	- for the link it is the linkID
	 * 	- for a route it's the domain ID, route ID, the language, the path parameters
	 * 		and the query string
	 * */
	public static function href(string $source, $data)
	{
		$lang = null;
		$baseHref = null;
		$queryStringParts = null;
		
		if ($source == 'link') {
			// $data has to be the link ID
			if (!$data || !isset(self::$links[$data])) {
				Debug::alert('Link with id ' . $data . ' not found.', 'f');
				return false;
			}
			
			// If the site is multilingual, we add the language marker to the URL
			if (Settings::get('multiLang')) {
				if (isset(self::$links[$data]['linkLang'])) {
					$lang = self::$links[$data]['linkLang'];
				} else {
					$lang = App::getLang();
				}

				$langMarker = DS . $lang;

				if (self::$links[$data]->path != '/') {
					if (isset(self::$links[$data]->path[$lang])) {
						$route = self::$links[$data]->path[$lang];
					} else {
						return false;
					}
				} else {
					$route = '/';
				}
			} else {
				$lang = App::getLang();
				$langMarker = '';
				if (self::$links[$data]->path != '/') {
					$route = self::$links[$data]->path[Settings::get('defaultLanguage')];
				} else {
					$route = '/';
				}
			}

			// Assembling path parameters
			$pathParams = '';

			foreach (self::$links[$data]->ppo as $id => $link) {
				$pathParams .= (isset(self::$links[$data]->pathParams[$link]))
					? '/' . self::$links[$data]->pathParams[$link]
					: '';
			}

			// The query string is stored in an array at this point
			if (!empty(self::$links[$data]->queryString)) {
				$queryStringParts = self::$links[$data]->queryString;
			} else {
				$queryStringParts = [];
			}

			$domain = self::$links[$data]->domain;

			$baseHref = self::$protocol
				. (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
				. $domain
				. $langMarker
				. $route
				. $pathParams;

		} elseif ($source == 'route') {
			/*
			 * href= "+route=19+lang=hu+pathParam1=abc+pathParam2=xyz"
			 * 
			 * @param data
			 * 	the href converted to an array
			 * */

			if (empty($data['route'])) {
				Debug::alert('Could not build href for route: parameters missing.', 'f');
				return false;
			}

			/*
			* If the language has not been set, we use the deafult language on the domain
			* */
			if (empty($data['lang']) || !Settings::get('multiLang')) {
				$lang = App::getLang();
			} else {
				$lang = $data['lang'];
				unset($data['lang']);
			}

			// Assembling the path
			// The remainder of the $data are the path parameters
			$pathParams = '';

			$routeRecord = self::getRouteRecordById($data['route']);
			
			if (!$routeRecord) {
				Debug::alert('Could not build href for route #' . $data['route'] . ': route missing.', 'f');
				return false;
			}

			if ($routeRecord->path !== '/') {
				$route = $routeRecord->path[$lang];
			} else {
				$route = $routeRecord->path;
			}

			$ppo = $routeRecord->moduleConfig['ppo'] ?? $routeRecord->modulePpo;

			if (!empty($ppo)) {
				foreach ($ppo as $param) {
					$pathParams .= isset($data[$param])
						? '/' . $data[$param]
						: '';
				}
			}

			$langMarker = Settings::get('multiLang') ? (DS . $lang) : '';

			// A queryStringParts array has to be returned, but we do not use it when
			// builing hrefs based on routes, so it'll be empty
			$queryStringParts = [];

			// If we are on a domain alias, we will use the paths with the current domain, otherwise use the original domain
			$domainId = IS_ALIAS && self::$aliasOf['id'] == $routeRecord->domainId ? DOMAIN_ID : $routeRecord->domainId ;
			
			$domain = self::getDomainRecordById($domainId);
			
			$baseHref = (IS_LOCALHOST ? 'http://' : self::$protocol)
				. (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
				. $domain['domain']
				. $langMarker
				. $route
				. $pathParams;
		} else {
			$baseHref = $data;
		}

		$return = [
			'lang' => $lang,
			'base' => $baseHref,
			'queryStringParts' => $queryStringParts
		];

		return $return;

	}

	/*
		Translates internal hrefs into URLs
	*/
	public static function url(string $xfwHref, array $overrides = [])
	{
		$url = null;
		$routerHref = [];
		$lang = App::getLang();
		
		if (substr($xfwHref, 0 ,2) === '//') {
			$xfwHref = self::getProtocol() . substr($xfwHref, 2);
		}

		// First character in the href determines what to do
		$xfwHref1 = $xfwHref[0];
		
		// Constructing the href
		if (in_array($xfwHref1, ['@', '+', '/'])) {

			$hrefParts = explode('?', $xfwHref, 2);

			// Converting the queryString to an array
			$queryStringParts = [];

			// Getting data already present in the query string
			if (isset($hrefParts[1])) {
				parse_str($hrefParts[1], $queryStringParts);
			}

			// Assembling the href part
			if ($xfwHref1 == '@') {
				//System link mode

				//The link will be generated based on the information stored in the
				//database.

				//Required values
				//	ID: the id of the link record in the database
				
				$linkId = substr($hrefParts[0], 1);
				$routerHref = self::href('link', $linkId);
			
			} elseif ($xfwHref1 == '+') {
				
				//Route mode

				//Required values
				//	route: the route ID
				//Optional values
				//	lang: language marker (en, hu etc.), the current language will be used if not given
				//		(if a route is not available, a 404 error will be thrown)
				//Usage example:
				//	href = "+route=19+lang=hu+pathParam1=abc+pathParam2=xyz"
				//	anchor = "sometext"
				//
				$data = [];

				$params = explode('+', substr($hrefParts[0], 1));

				foreach ($params as $p) {
					$cp = explode('=', $p, 2);

					$data[trim($cp[0])] = trim($cp[1]);
				}

				$routerHref = self::href('route', $data);

			} else {
				// Starts with a /
				$routerHref = [
					'lang' => $lang,
					'base' => self::gethostURL() . $hrefParts[0],
					'queryStringParts' => $queryStringParts
				];
			}
			
			if (is_array($routerHref) && $routerHref['base']) {
				// Adding the page number to the query string
				if (!empty($overrides['pageNumber'])) {
					$queryStringParts[self::getPaginationParams()[$routerHref['lang']]] = $overrides['pageNumber'];
				}

				$queryStringParts = array_merge($queryStringParts, $routerHref['queryStringParts']);

				if (isset($overrides['queryParams']) && is_array($overrides['queryParams'])) {
					$queryStringParts = array_merge($queryStringParts, $overrides['queryParams']);
				}

				// Elements in the query string can be removed with the remove moduleVar
				if (isset($overrides['remove']) && is_array($overrides['remove'])) {
					$queryStringParts = array_diff_key($queryStringParts, array_flip($overrides['remove']));
				}

				// The directly given parameters will override the saved ones
				$queryString = http_build_query($queryStringParts);

				// Adding the questionmark if it was not present
				if ($queryString && strpos($routerHref['base'], '?') === false) {
					$queryString = '?' . $queryString;
				}
				$url = $routerHref['base'] . $queryString;
			}
		} elseif ($xfwHref1 == '?') {
			$queryParameters = [];
			parse_str(substr($xfwHref, 1), $queryParameters);
			$queryString = http_build_query(array_merge(self::$queryParameters, $queryParameters));
			$url = self::$noQueryStringUrl . '?' . $queryString;
		} else {
			$url = $xfwHref;
		}

		return $url;
	}

}