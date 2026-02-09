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

use stdClass;

use function Arembi\Xfw\Misc\getFileExtension;
use function Arembi\Xfw\Misc\md_array_lookup_key;

abstract class Router {

	private static $model;
	private static $aliasOf; // Whether the domain acts as an alias of another
	private static $protocol; // Http, https
	private static $hostUrl; // Protocol + domain
	private static $path; // Equivalent to Apache's %{REQUEST_URI}
	private static $urlNoQueryString; // The URL without the query string
	private static $fullUrl; // Protocol + domain + path + query string
	private static $pathNodes; // The segments of the path
	private static $pathNoQueryString; // The path without the query string
	private static $queryString;
	private static $queryParameters;
	private static $pathParameters; // The parameters given to the primary modules via the path (query string not included)
	private static $domains; // The registered domains, will not be loaded automatically
	private static $redirects; // Custom redirects from the DB
	private static $links; // Saved links in the system
	private static $routes; // Defined routes in the system
	private static $primaryModuleRoutes; // Available primary modules
	private static $backendModuleRoutes; // Available backend modules
	private static $hit404; // Shall be set to true if a 404 error occures
	private static $matchedRoute; // The matched route's record
	private static $pageNumber; // The number of the page used by the pagination
	private static $paginationParameters; // Collection of URL parameters for the page number
	private static $paginationParameter; // The default page numbering URL parameter on the domain; eg. page, oldal, seite
	private static $inputInfo;
	private static $inputHandlerResult;
	private static $GET; // Global clone
	private static $POST; // Global clone
	private static $REQUEST; // Global clone
	private static $FILES; // Global clone


	public static function init(): void
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
		self::$pathParameters = [];
		self::$domains = [];
		self::$redirects = [];
		self::$links = [];
		self::$routes = [];
		self::$primaryModuleRoutes = [];
		self::$backendModuleRoutes = [];
		self::$hit404 = false;
		self::$pageNumber = null;
		
		self::$matchedRoute = null;
		self::$paginationParameters = [];
		self::$paginationParameter = '';
		
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


	public static function getPathParameters(): array
	{
		return self::$pathParameters;
	}


	public static function getPaginationParameters(): array
	{
		return self::$paginationParameters;
	}


	public static function getPaginationParameter(): string
	{
		return self::$paginationParameter;
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


	public static function getDomainRecordById(int $id)
	{
		return self::$domains[$id] ?? false;
	}


	public static function getDomainId(string $domain)
	{
		return md_array_lookup_key(self::$domains, 'domain', $domain);
	}


	public static function getRouteRecordById(int $routeId)
	{
		return self::$routes->first(fn($r) => $r->id == $routeId);
	}


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


	public static function getQueryParameters()
	{
		return self::$queryParameters;
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
			'pathParameters' => self::$pathParameters,
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
				self::$paginationParameters = Settings::get('paginationParameter');
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
		define('UPLOADS_DIR', Config::get('uploadsDir') . DS . DOMAIN);
		
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
		
		self::$redirects = self::$domains[DOMAIN_ID]->redirects;

		self::$routes = self::$model->getAvailableRoutes(DATA_DOMAIN_ID);
		
		foreach (self::$routes as $r) {
			if ($r->moduleClass == 'b') {
				self::$backendModuleRoutes[$r->id] = $r;
			} else {
				self::$primaryModuleRoutes[$r->id] = $r;
			}
		}
	}


	public static function parsePath()
	{
		// First the path nodes and the main path need to be copied,
		// so the modifications during the parsing will not overwrite them
		$pathNodesWorkpiece = self::$pathNodes;
		$pathWorkpiece  = self::$pathNoQueryString;

		/*
		 * TRAILING SLASHES
		 *  force: every URL ends with a slash
		 *  remove: none of them ends with a slash
		 *  both
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

		self::handleLanguage($pathNodesWorkpiece, $pathWorkpiece);

		
		Router::autoRedirect();


		if (!self::$hit404 && !self::$routes) {
			return [
				'primary'=>'unauthorized',
				'action'=>'default',
				'documentLayout'=>Settings::get('defaultDocumentLayout')
			];
		}

		self::$paginationParameters = Settings::get('paginationParameter');
		self::$paginationParameter = self::$paginationParameters[App::getLang()];
		self::$pageNumber = !empty(self::$GET[self::$paginationParameter]) ? self::$GET[self::$paginationParameter] : null;

		return self::matchRoute($pathWorkpiece);
	}


	private static function handleLanguage(&$pathNodes, &$path): void
	{
		/*
		 * LANGUAGE MARKERS
		 * The indicator in the URL can only be the first segment of the path
		 * for instance example.com/en
		 * 
		 * Language markers will overwrite the default or the session language
		 * 
		 * The segment marking the language will be removed from the path
		 */
		
		$detectedLanguage = $_SESSION['lang'] ?? Settings::get('defaultLanguage');
		
		if (Settings::get('multiLang') == 'true') {
			$languageSetInUrl = false;

			$langIndex = 0;
			$availableLanguages = Settings::get('availableLanguages');
			$numberOfAvailableLanguages = count($availableLanguages);
			
			while (!$languageSetInUrl && $langIndex < $numberOfAvailableLanguages) {
				if ($pathNodes[0] == $availableLanguages[$langIndex][0]) {
					$languageSetInUrl = true;
				} else {
					$langIndex++;
				}
			}

			if ($languageSetInUrl) {
				$detectedLanguage = $pathNodes[0];
				$path = mb_substr($path, mb_strlen($pathNodes[0]) + 1); // Removing the language segment from the path to process
				array_shift($pathNodes); // Removing the language segment from the path nodes
			}

			$_SESSION['lang'] = $detectedLanguage;
			
			// Redirecting URLs without the language markers to the appropriate URL
			$correctLanguageUrl = 
				self::$hostUrl
				. DS
				. $detectedLanguage
				. (self::$path == '/' && Settings::get('URLTrailingSlash') == 'remove' ? '' : $path)
				. (self::$queryString ? '?' : '')
				. self::$queryString;

			if ($correctLanguageUrl != self::$fullUrl) {
				self::redirect($correctLanguageUrl, 302);
			}
		}
		Debug::alert('Language: ' . $detectedLanguage);
		App::setLang($detectedLanguage);
	}


	// Returns the corresponding document layout, the primary module and its action to execute, based on the URI
	public static function matchRoute(string $path)
	{
		if (self::$hit404) {
			return self::assemble404Module();
		}

		$lang = App::getLang();
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
			
			foreach (self::$primaryModuleRoutes as $route) {
				if (isset($route->path[$lang])) { // the root won't be a contender because of this
					if (strpos($path, $route->path[$lang]) === 0 ) {
						/*
						The whole segment has to match, so we need to check the following
						character after the candidate route
						Example where things could go wrong:
						/pages/page1
						/pages/page10
						here the route /pages/page1 would match both paths
						*/

						$length = mb_strlen($route->path[$lang]);
						$nextChar = mb_substr($path, $length, 1);
						$accuracy = mb_substr_count($route->path[$lang], '/');

						if (($nextChar == '/' || !$nextChar) && $accuracy > $bestMatch['accuracy']) {
							$bestMatch = [
								'match' => $route,
								'accuracy' => $accuracy
							];
						}
					}
				}
			}

			// The primary module has been found, the remainder of the path consists of the path parameters
			if ($bestMatch['match'] !== false) {
				// +1 for the array indexing and another +1 for the starting slash
				self::$pathParameters = explode('/', mb_substr($path, mb_strlen($bestMatch['match']->path[App::getLang()]) + 1 ));
				self::$matchedRoute = $bestMatch['match'];
			}
		}

		if (null === self::$matchedRoute) {
			self::$matchedRoute = self::assemble404Module();
		}

		if (!$_SESSION['user']->isAllowedHere()) {
			self::$matchedRoute->moduleName = 'unauthorized';
		}
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
			$publicDir = Settings::get('publicFilesDir') ?? 'public';
			$relativePath = ltrim(self::$pathNoQueryString, '/');

			if ($publicDir === '' || $publicDir === null) {
				return;
			}

			if (str_contains($relativePath, '..')) {
				App::hcf('Invalid file path.');
			}

			if (!str_starts_with($relativePath, $publicDir . '/')) {
				return;
			}

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
		$module = new stdClass();
		$module->moduleName = 'fourohfour';
		$module->action = 'default';
		$module->clearanceLevel = 0;
		$module->documentLayout = Settings::get('defaultDocumentLayout');
		$module->documentLayoutVariant = Settings::get('defaultDocumentLayoutVariant');

		return $module;
	}


	public static function redirect(string $url, int $statusCode = 302): void
	{
		
		$location = self::url($url);
		
		if (null !== $location) {
			// Preventing permanent redirects in development environment
			if (IS_LOCALHOST) {
				$statusCode = 302;
			}
			
			header('Location: ' . $location, true, $statusCode);
			exit;
		} else {
			Debug::alert('Cannot redirect URL, destination is missing.', 'f');
		}
		
	}


	public static function autoRedirect()
	{
		self::$redirects->each(function ($redirect) {
			if (preg_match('/' . $redirect->rule . '/', self::$path)) {
				self::redirect($redirect->destination, $redirect->type);
			}
		});
	}


	public static function hit404(): void
	{
		header('HTTP/1.1 404 Not Found');
		self::$hit404 = true;
	}


	public static function loadDomains(): void
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
	public static function linkToUrl(int $linkId): array|false
	{
		$lang = '';
		
		$urlToReturn = [
			'lang' => '',
			'href' => ''
		];

		if (!isset(self::$links[$linkId])) {
			Debug::alert('Link #' . $linkId . ' not found.', 'f');
			return false;
		}

		$link = self::$links[$linkId];
		$lang = $link->linkLang ?? App::getLang();
		$urlToReturn = self::routeToUrl($link->routeId, $lang, $link->pathParameters, $link->queryParameters);

		return $urlToReturn;
	}


	public static function routeToUrl(int $routeId, string $lang, array $pathParameters = [], array $queryParameters = []): array|false
	{
		$path = '';
		$ppo = [];
		$pathParametersSegment = '';
		$url = '';
		$queryString = '';
		$langMarker = Settings::get('multiLang') ? (DS . $lang) : '';
		
		$urlToReturn = [
			'lang' => '',
			'href' => ''
		];
		
		$routeRecord = self::getRouteRecordById($routeId);

		if (!$routeRecord) {
			Debug::alert('Could not build href for route #' . $routeId . ': route missing.', 'f');
			return false;
		}

		if (!isset($routeRecord->path[$lang])) {
			Debug::alert('Could not build href for route #' . $routeId . ': path in language [' . $lang . '] missing.', 'w');
			return false;
		}

		$path = $routeRecord->path[$lang] !== '/' ? $routeRecord->path[$lang] : '';

		$ppo = $routeRecord->moduleConfig['ppo'] ?? $routeRecord->modulePpo;

		// TODO: revise if it should be allowed to generate href if a path parameter is missing
		// The path parameters can be either arrays, when there are more available languages or strings, when there is only one available language
		if (!empty($ppo)) {
			foreach ($ppo as $parameter) {
				if (!isset($pathParameters[$parameter])) {
					continue;
				}
				$currentParameter = is_array($pathParameters[$parameter])
					? ($pathParameters[$parameter][$lang] ?? '')
					: (is_string($pathParameters[$parameter])
						? '/' . $pathParameters[$parameter]
						: '');
				if ($currentParameter) {
					$pathParametersSegment .= $currentParameter;	
				}
			}
		}

		if (Settings::get('URLTrailingSlash') == 'force') {
			$pathParametersSegment .= '/';
		}

		$queryString = http_build_query($queryParameters);

		// If we are on an alias domain, we will use the paths with the current domain, otherwise use the original domain
		$domainId = IS_ALIAS && self::$aliasOf['id'] == $routeRecord->domainId ? DOMAIN_ID : $routeRecord->domainId ;
		$domain = self::getDomainRecordById($domainId);
		
		$url = (IS_LOCALHOST ? 'http://' : $domain->protocol)
			. (IS_LOCALHOST ? $_SERVER['HTTP_HOST'] . DS . WEB_ROOT . DS : '')
			. $domain['domain']
			. $langMarker
			. $path
			. $pathParametersSegment
			. ($queryString ? '?' . $queryString : '');

		$urlToReturn = [
			'lang' => $lang,
			'href' => $url
		];

		return $urlToReturn;
	}


	/*
		Deconstructs a href string, and builds the url that corresponds the environment
		href types:
		 - route parameters + query string
		 - link ID - links stored in the DB
		 - freehand URLs
	*/
	public static function url(string $internalHref, array $overrides = [], bool $returnLang = false): string|array|false
	{
		$urlToReturn = '';
		$langToReturn = '';
		$routerHref = [];
		$internalHrefFirstChar = '';
		$internalHrefParts = [];
		$queryParameters = [];
		$liveQueryString = '';
		
		if (mb_substr($internalHref, 0 , 2) === '//') {
			$internalHref = self::getProtocol() . mb_substr($internalHref, 2);
		}

		$internalHrefFirstChar = mb_substr($internalHref, 0, 1);
		
		if (in_array($internalHrefFirstChar, ['@', '+', '/'])) { // Special href
			
			$internalHrefParts = explode('?', $internalHref, 2);

			if ($internalHrefFirstChar == '@') { // Link mode
				// Required parameters
				// linkId
				// Example:
				// href="@56"
				$linkId = mb_substr($internalHrefParts[0], 1);

				$routerHref = self::linkToUrl($linkId);

				if (isset($routerHref['queryParameters'])) {
					$queryParameters = $routerHref['queryParameters'];
				}
			} elseif ($internalHrefFirstChar == '+') { // Route mode
				// Required parameters
				// route: the route ID
				// Example:
				// href = "+route=19+lang=en+pathParameter1=abc+pathParameter2=xyz"
				// If the route is not set, the current route will be used
				$pathParameters = [];
				
				$hrefParameters = explode('+', mb_substr($internalHrefParts[0], 1));
				
				foreach ($hrefParameters as $keyValuePair) {
					$parameterParts = explode('=', $keyValuePair, 2);
					$pathParameters[trim($parameterParts[0])] = trim($parameterParts[1]);
				}

				$pathParameters = array_merge($pathParameters, $overrides);
				$routeId = $pathParameters['route'] ?? self::getMatchedRouteId();
				$routeLang = $pathParameters['lang'] ?? App::getLang() ?: Settings::get('defaultLanguage');
				
				if (isset($internalHrefParts[1])) {
					parse_str($internalHrefParts[1], $queryParameters);
				}

				// Adding the page number to the query string
				if (!empty($overrides['pageNumber'])) {
					$queryParameters[self::getPaginationParameters()[$routerHref['lang']]] = $overrides['pageNumber'];
				}

				if (isset($overrides['queryParameters']) && is_array($overrides['queryParameters'])) {
					$queryParameters = array_merge($queryParameters, $overrides['queryParameters']);
				}

				// Elements in the query string can be removed with the remove parameter
				if (isset($overrides['remove']) && is_array($overrides['remove'])) {
					$queryParameters = array_diff_key($queryParameters, array_flip($overrides['remove']));
				}
				
				$routerHref = self::routeToUrl($routeId, $routeLang, $pathParameters, $queryParameters);

			} else { // Starts with a /
				$routerHref = [
					'lang' => $overrides['lang'] ?? App::getLang(),
					'href' => self::gethostURL() . $internalHrefParts[0]
				];
			}
			
			// Assembling the query string
			if (!is_array($routerHref) || !$routerHref['href']) {
				return false;
			}
				
			$urlToReturn = $routerHref['href'] . $liveQueryString;
			$langToReturn = $routerHref['lang'];

		} elseif ($internalHrefFirstChar == '?') { // Path stays the same, the query string will be merged with the existing one
			
			parse_str(mb_substr($internalHref, 1), $queryParameters);
			$liveQueryString = http_build_query(array_merge(self::$queryParameters, $queryParameters));
			$urlToReturn = self::$urlNoQueryString . '?' . $liveQueryString;
			$langToReturn = App::getLang();

		} else { // A full URL has been given, no changes needed
			
			$langToReturn = App::getLang();
			$urlToReturn = $internalHref;

		}

		return $returnLang ? ['lang'=>$langToReturn, 'url'=>$urlToReturn] : $urlToReturn;
	}

}
