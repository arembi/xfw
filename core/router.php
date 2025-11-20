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

use function Arembi\Xfw\Misc\getFileExtension;
use function Arembi\Xfw\Misc\md_array_lookup_key;

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
	private static $urlNoQueryString;

	// Protocol + domain + path + query string
	private static $fullUrl;

	// Parts of the path
	private static $pathNodes; // The segments of the path
	private static $pathNoQueryString; // The path without the query string
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
		self::$urlNoQueryString = '';
		self::$fullUrl = '';
		self::$pathNodes = [];
		self::$pathNoQueryString = '';
		self::$queryString = '';
		self::$queryParameters = [];
		self::$pathParams = [];
		self::$domains = [];
		self::$redirects = [];
		self::$links = [];
		self::$routes = [];
		self::$primaryModuleRoutes = [];
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


	public static function getRequestedDocumentAction()
	{
		return self::getMatchedRouteDocumentAction();
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
		return md_array_lookup_key(self::$domains, 'domain', $domain);
	}


	public static function getRouteRecordById(int $routeId)
	{
		return self::$routes->first(fn($r) => $r->id == $routeId);
	}


	// Returns the actual route (/lang/some/fancy/url) for the given route ID
	public static function getPathByRouteId(int $routeId, ?string $lang = null)
	{
		$ret = null;

		if ($lang === null) {
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
		return self::$urlNoQueryString;
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


	public static function getMatchedRouteDocumentAction()
	{
		return self::$matchedRoute->moduleConfig['documentAction'] ?? null;
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


	public static function getIdByRoute(string $path, string $type = 'all'): int|false
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


	public static function urlInfo(): array
	{
		return [
			'protocol' => self::$protocol,
			'hostUrl' => self::$hostUrl,
			'path' => self::$path,
			'urlNoQueryString' => self::$urlNoQueryString,
			'fullUrl' => self::$fullUrl,
			'pathNodes' => self::$pathNodes,
			'pathNoQueryString' => self::$pathNoQueryString,
			'queryString' => self::$queryString,
			'queryParameters' => self::$queryParameters,
			'pathParams' => self::$pathParams,
			'pageNumber' => self::$pageNumber
		];
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
	public static function getEnvironment(): void
	{
		$uri = '';
		$uriQuestionmarkParts = [];
		$uriParts = [];
		$domain = '';
		$domainId = null;
		$domainRecord = null;
		$mirroredDomainId = null;
		$mirroredDomainRecord = null;
		$dataDomainId = null;
		$isLocalhost = true;
		$webRoot = '';
		$requestProtocol = '';
		
		$uri = urldecode($_SERVER['REQUEST_URI']);
		$isLocalhost = self::amIOnLocalhost();

		// Cutting off the query string
		$uriQuestionmarkParts = explode('?', $uri, 2);

		if (isset($uriQuestionmarkParts[1])) {
			self::$queryString = $uriQuestionmarkParts[1];
			parse_str(self::$queryString, self::$queryParameters);
		}

		/*
		Checking whether it is a development environment or not
		(assuming that dev is mainly performed on localhost)
		*/
		if ($isLocalhost) {
			$uriParts = explode('/', $uriQuestionmarkParts[0]);

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

		self::$paginationParams = Settings::get('paginationParam');
				$domain = $uriParts[2];
				self::$pathNoQueryString = '/' . implode('/', array_slice($uriParts, 3));
				self::$path = self::$pathNoQueryString . (isset($uriQuestionmarkParts[1]) ? '?' . $uriQuestionmarkParts[1] : '');
			} else {
				App::hcf(file_get_contents(ENGINE_DIR . DS . 'welcome.html'), false);
			}
		} else {
			$domain = $_SERVER['HTTP_HOST'];

			self::$pathNoQueryString = $uriQuestionmarkParts[0];
			self::$path = $uri;
		}
		
		$domainId = self::getDomainId($domain);
		
		if ($domainId) {
			$domainRecord = self::getDomainRecordById($domainId);
		}

		if (!$domainRecord) {
			App::hcf(file_get_contents(ENGINE_DIR . DS . '404.html'), false);
		}

		/* 
			ALIASES
			If a domain is marked as an alias of another via its domain settings, the alias will inherit
			all settings, i.e routes, content from the original domain
		*/
		$mirroredDomainId = $domainRecord['settings']['aliasOf'] ?? false;
		
		if ($mirroredDomainId) {
			$mirroredDomainRecord = self::getDomainRecordById($mirroredDomainId);
			if ($mirroredDomainRecord 
				&& isset($mirroredDomainRecord['settings']['aliases'])
				&& in_array($domainId, $mirroredDomainRecord['settings']['aliases'])) {
				self::$aliasOf = [
					'id'=>$mirroredDomainId,
					...(array) $mirroredDomainRecord
				];
				Debug::alert('ALIAS MODE, ALTERING DATA MIGHT AFFECT OTHER DOMAINS!', 'w');
				Debug::alert('Domain identified as alias for ' . $mirroredDomainRecord['domain'], 'n');
			}
		}
		
		$dataDomainId = self::$aliasOf['id'] ?? $domainId;
		
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

		self::$urlNoQueryString = self::$hostUrl . self::$pathNoQueryString;

		self::$pathNodes = explode('/', mb_substr(self::$pathNoQueryString, 1)); // Initial '/' is removed
	}


	private static function amIOnLocalhost(): bool
	{
		return (in_array($_SERVER['REMOTE_ADDR'], Config::get('localhostIP'))
			|| strpos($_SERVER['REMOTE_ADDR'], '192.168') !== false);
	}


	public static function loadData(): void
	{
		self::$links = self::$model->getSystemLinksByDomain();

		self::$redirects = self::$model->getRedirects();

		self::$routes = self::$model->getAvailableRoutes(DATA_DOMAIN_ID);
	}


	public static function parsePath()
	{
		$lang = '';
		
		/*
		TRAILING SLASHES
			force: every URL ends with a slash
			remove: none of them ends with a slash
			both
		*/
		if (Settings::get('URLTrailingSlash') == 'remove') {
			if (self::$pathNoQueryString != '/' && mb_substr(self::$pathNoQueryString, -1) == '/') {
				self::redirect(mb_substr(self::$fullUrl, 0, -1));
			}
		} elseif (Settings::get('URLTrailingSlash') == 'force') {
			if (mb_substr(self::$pathNoQueryString, -1) != '/') {
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
		$pathNodesWorkpiece = self::$pathNodes;
		$pathWorkpiece  = self::$pathNoQueryString;

		/*
		 * If the site is multilingual, we have to detect which language
		 * the user wants to load, doing that by calling self::handleLanguage()
		 * */
		
		$lang = Settings::get('multiLang') == 'true' ? self::handleLanguage($pathNodesWorkpiece, $pathWorkpiece) : Settings::get('defaultLanguage');
		Debug::alert('Language: ' . $lang);
		App::setLang($lang);
		$_SESSION['lang'] = $lang;
		
		$correctUrl = self::$hostUrl . DS . $lang . (self::$path == '/' && Settings::get('URLTrailingSlash') == 'remove' ? '' : $pathWorkpiece);

		if ($correctUrl != self::$fullUrl) {
			self::redirect($correctUrl, 302);
		}
		

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

		return self::matchRoute($pathWorkpiece);
	}


	private static function handleLanguage(&$pathNodes, &$path)
	{
		// The available languages are stored in an array for each language,
		// beginning with the language marker used in this system (f.i. "en"), followed by other aliases,
		// (f.i. "en-GB")
		// The segment marking the language will be removed from the path

		$langSetInURL = false;

		$langIndex = 0;
		$availableLanguages = Settings::get('availableLanguages');
		$numberOfAvailableLanguages = count($availableLanguages);
		
		while (!$langSetInURL && $langIndex < $numberOfAvailableLanguages) {
			if ($pathNodes[0] == $availableLanguages[$langIndex][0]) {
				$langSetInURL = true;
			} else {
				$langIndex++;
			}
		}

		if ($langSetInURL) {
			// The language was set in the URL, it might override previous values
			$lang = $pathNodes[0];
			$path = mb_substr($path, mb_strlen($pathNodes[0]) + 1); // Removing the language segment from the path to process
			array_shift($pathNodes); // Removing the language segment from the path nodes
		} else {
			$lang = !empty($_SESSION['lang']) ? $_SESSION['lang'] : Settings::get('defaultLanguage');
		}

		return $lang;
	}


	// Returns the corresponding document layout, the primary module and its action to execute, based on th URI
	public static function matchRoute(string $path)
	{
		if (self::$hit404) {
			return self::assemble404Module();
		}

		$lang = App::getLang();
		$match = [];

		/*
		Processing the module part
		If the site is multilingual, then at this point, the language marker in the URL has been already removed
		*/

		// The root route is a special URI, works rather differently form the 'normal' ones
		if ($path == '/' || $path == '') {
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
				$match['documentLayout'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['documentLayout'] ?? null;
				$match['documentLayoutVariant'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['documentLayoutVariant'] ?? null;
				$match['primary'] = self::$primaryModuleRoutes[$rootId]->moduleName;
				$match['action'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['action'] ?? null;
				$match['params'] = self::$primaryModuleRoutes[$rootId]->moduleConfig['params'] ?? [];

				self::$matchedRoute = self::$primaryModuleRoutes[$rootId];
			} else {
				return self::assemble404Module();
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
			
			foreach (self::$primaryModuleRoutes as $r) {
				if (isset($r->path[$lang])) { // the root won't be a contender because of this
					if (strpos($path, $r->path[$lang]) === 0 ) {
						/*
						The whole segment has to match, so we need to check the following
						character after the candidate route
						Example where things could go wrong:
						/pages/page1
						/pages/page10
						here the route /pages/page1 would match both paths
						*/

						$length = mb_strlen($r->path[$lang]);
						$nextChar = mb_substr($path, $length, 1);
						$accuracy = mb_substr_count($r->path[$lang], '/');

						if (($nextChar == '/' || !$nextChar) && $accuracy > $bestMatch['accuracy']) {
							$bestMatch = [
								'match' => $r,
								'accuracy' => $accuracy
							];
						}
					}
				}
			}

			// The primary module has been found, the remainder of the path consists of the path parameters
			if ($bestMatch['match'] !== false) {
				// +1 for the array indexing and another +1 for the starting slash
				self::$pathParams = explode('/', mb_substr($path, mb_strlen($bestMatch['match']->path[App::getLang()]) + 1 ));
				self::$matchedRoute = $bestMatch['match'];
				$match['primary'] = self::$matchedRoute->moduleName;
				$match['action'] = self::$matchedRoute->moduleConfig['action'] ?? null;
				$match['params'] = self::$matchedRoute->moduleConfig['params'] ?? [];
				$match['documentLayout'] = self::$matchedRoute->moduleConfig['documentLayout'] ?? null;
				$match['documentLayoutVariant'] = self::$matchedRoute->moduleConfig['documentLayoutVariant'] ?? null;
			} else {
				$match['documentLayout'] = null;
				$match['documentLayoutVariant'] = null ;
			}
		}

		if (!isset($match['primary'])) {
			// Primary module match not found
			$match = self::assemble404Module();
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


	public static function processInput()
	{
		$result = null;

		if (!empty(self::$REQUEST['formId'])) {
			$result = Input_Handler::processStoredForm(self::$REQUEST['formId']);

			self::$inputInfo = [
				'mode'=>'form',
				'data'=>[
					'id'=>self::$REQUEST['formId'],
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

		if ($result) {
			if ($result->status() == Input_Handler::RESULT_SUCCESS) {
				Debug::alert('Input processing result: ' . $result->message(), 'o');
				self::inputHandlerResult($result);
			} elseif ($result->status() == Input_Handler::RESULT_ERROR) {
				Debug::alert('Error while processing input: ' .  $result->message(), 'f');
			}
		} else {
			Debug::alert('No input has been sent.');
		}
	}


	public static function serveFiles()
	{
		$extension = getFileExtension(self::$pathNoQueryString);
		
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

				if ($fs->fileExists(self::$pathNoQueryString)) {
					$mime = $fs->mimeType(self::$pathNoQueryString) ?? 'text/plain';
					header('Content-Type: ' . $mime);
					echo $fs->read(self::$pathNoQueryString);
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
	 * its source, builds the path up to the query string (hrefBase)
	 * returns the hrefBase and the query string as an array for further processing
	 * @param $source: supported sources are `link` and `route`
	 * @param $data: infrotmation used to generate the href
	 * 	- for the link it is the linkID
	 * 	- for a route it's the domain ID, route ID, the language, the path parameters
	 * 		and the query string
	 * */
	public static function linkToHref(int $id): array|false
	{
		$lang = '';
		$hrefBase = '';
		$queryStringParts = [];
		$pathParams = '';
		$langMarker = '';
		$route = '';
		$href = [
			'lang' => '',
			'base' => '',
			'queryStringParts' => []
		];

		// $data has to be the link ID
		if (!isset(self::$links[$id])) {
			Debug::alert('Link with ID ' . $id . ' not found.', 'f');
			return false;
		}
		
		// If the site is multilingual, we add the language marker to the URL
		if (Settings::get('multiLang')) {
			
			$lang = self::$links[$id]['linkLang'] ?? App::getLang();

			$langMarker = DS . $lang;

			if (self::$links[$id]->path != '/') {
				if (isset(self::$links[$id]->path[$lang])) {
					$route = self::$links[$id]->path[$lang];
				} else {
					return false;
				}
			} else {
				$route = '/';
			}
		} else {
			$lang = App::getLang();
			$langMarker = '';
			if (self::$links[$id]->path != '/') {
				$route = self::$links[$id]->path[Settings::get('defaultLanguage')];
			} else {
				$route = '/';
			}
		}

		// Assembling path parameters
		foreach (self::$links[$id]->ppo as $link) {
			$pathParams .= (isset(self::$links[$id]->pathParams[$link]))
				? '/' . self::$links[$id]->pathParams[$link]
				: '';
		}

		// The query string is stored in an array at this point
		if (!empty(self::$links[$id]->queryString)) {
			$queryStringParts = self::$links[$id]->queryString;
		}

		$domain = self::$links[$id]->domain;

		$hrefBase = self::$protocol
			. (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
			. $domain
			. $langMarker
			. $route
			. $pathParams;
		
		$href = [
			'lang' => $lang,
			'base' => $hrefBase,
			'queyStringParts'=>$queryStringParts
		];

		return $href;
	}


	public static function routeToHref(array $data): array|false
	{
		/*
			Example:
			[
				'route'=>ID,
				'lang'=>'en',
				'pathParam1'=>'value1',
				'pathParam2'=>value2
			]
		*/
		$lang = '';
		$hrefBase = '';
		$pathParams = '';
		$langMarker = '';
		$href = [
			'lang' => '',
			'base' => ''
		];
		
		if (empty($data['route'])) {
			Debug::alert('Could not build href for route: parameters missing.', 'f');
			return false;
		}

		// If the language has not been set, we use the deafult language on the domain
		if (empty($data['lang']) || !Settings::get('multiLang')) {
			$lang = App::getLang();
		} else {
			$lang = $data['lang'];
		}

		$routeRecord = self::getRouteRecordById($data['route']);
		
		if (!$routeRecord) {
			Debug::alert('Could not build href for route #' . $data['route'] . ': route missing.', 'f');
			return false;
		}

		if ($routeRecord->path !== '/') {
			if (!isset($routeRecord->path[$lang])) {
				Debug::alert('Could not build href for route #' . $data['route'] . ': path in language [' . $lang . '] missing.', 'w');
				return false;
			}
			$path = $routeRecord->path[$lang];
		} else {
			$path = $routeRecord->path;
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

		// If we are on an alias domain, we will use the paths with the current domain, otherwise use the original domain
		$domainId = IS_ALIAS && self::$aliasOf['id'] == $routeRecord->domainId ? DOMAIN_ID : $routeRecord->domainId ;
		
		$domain = self::getDomainRecordById($domainId);
		
		$hrefBase = (IS_LOCALHOST ? 'http://' : self::$protocol)
			. (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
			. $domain['domain']
			. $langMarker
			. $path
			. $pathParams;

		$href = [
			'lang' => $lang,
			'base' => $hrefBase
		];

		return $href;

	}


	/*
		Translates hrefs intro URLs
	*/
	public static function url(string $xfwHref, array $overrides = [], bool $returnLang = false): string|array|false
	{
		$urlToReturn = '';
		$langToReturn = '';
		$routerHref = [];
		$xfwHrefFirstChar = '';
		$xfwHrefParts = [];
		$queryStringParts = [];
		
		if (mb_substr($xfwHref, 0 ,2) === '//') {
			$xfwHref = self::getProtocol() . mb_substr($xfwHref, 2);
		}

		$xfwHrefFirstChar = mb_substr($xfwHref, 0, 1);
		
		if (in_array($xfwHrefFirstChar, ['@', '+', '/'])) { // Link mode
			
			$xfwHrefParts = explode('?', $xfwHref, 2);

			if (isset($xfwHrefParts[1])) {
				parse_str($xfwHrefParts[1], $queryStringParts);
			}

			// Assembling the path part
			if ($xfwHrefFirstChar == '@') { //System link mode
				//	linkId: an integer that follows the @
				$linkId = mb_substr($xfwHrefParts[0], 1);

				$routerHref = self::linkToHref($linkId);
				$queryStringParts = array_merge($queryStringParts, $routerHref['queryStringParts']);
			} elseif ($xfwHrefFirstChar == '+') { // Route mode
				// Required parameters
				// route: the route ID
				// Example:
				// href = "+route=19+lang=en+pathParam1=abc+pathParam2=xyz"
				$routeData = [];
				
				if (isset($overrides['lang'])) {
					$routeData['lang'] = $overrides['lang'];
				}
				
				$hrefParams = explode('+', mb_substr($xfwHrefParts[0], 1));
				
				foreach ($hrefParams as $p) {
					$cp = explode('=', $p, 2);
					$routeData[trim($cp[0])] = trim($cp[1]);
				}

				$routerHref = self::routeToHref($routeData);

			} else { // Starts with a /
				$routerHref = [
					'lang' => $overrides['lang'] ?? App::getLang(),
					'base' => self::gethostURL() . $xfwHrefParts[0]
				];
			}
			
			if (is_array($routerHref) && $routerHref['base']) {
				// Adding the page number to the query string
				if (!empty($overrides['pageNumber'])) {
					$queryStringParts[self::getPaginationParams()[$routerHref['lang']]] = $overrides['pageNumber'];
				}

				if (isset($routerHref['queryStringParts'])) {
					$queryStringParts = array_merge($queryStringParts, $routerHref['queryStringParts']);
				}

				if (isset($overrides['queryParams']) && is_array($overrides['queryParams'])) {
					$queryStringParts = array_merge($queryStringParts, $overrides['queryParams']);
				}

				// Elements in the query string can be removed with the remove parameter
				if (isset($overrides['remove']) && is_array($overrides['remove'])) {
					$queryStringParts = array_diff_key($queryStringParts, array_flip($overrides['remove']));
				}

				// The directly given parameters will override the saved ones
				$queryString = http_build_query($queryStringParts);

				// Adding the questionmark if it was not present
				if ($queryString && strpos($routerHref['base'], '?') === false) {
					$queryString = '?' . $queryString;
				}

				$urlToReturn = $routerHref['base'] . $queryString;

			} else {
				return false;
			}

			$langToReturn = $routerHref['lang'];

		} elseif ($xfwHrefFirstChar == '?') {
			// Path stays the same, the query string will be merged with the existing one
			$queryParameters = [];
			parse_str(mb_substr($xfwHref, 1), $queryParameters);
			$queryString = http_build_query(array_merge(self::$queryParameters, $queryParameters));
			$urlToReturn = self::$urlNoQueryString . '?' . $queryString;
			$langToReturn = App::getLang();
		} else {
			// A full URL has been given, no changes needed
			$urlToReturn = $xfwHref;
			$langToReturn = App::getLang();
		}

		return $returnLang ? ['lang'=>$langToReturn, 'url'=>$urlToReturn] : $urlToReturn;
	}

}