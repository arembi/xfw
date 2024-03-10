<?php

/*
 * Every request MUST flow through this script
 * Includes the App class, which will coordinate the whole process
 * */
namespace Arembi\Xfw\Core;

// System directories
define('DS', DIRECTORY_SEPARATOR);
define('BASE', __DIR__);
define('ENGINE', BASE . DS . '..' . DS .'xfw_engine' );
define('CORE', ENGINE . DS . 'core');
define('SITES', BASE . DS . '..' . DS . 'xfw_sites');

// Loading application engine
require_once(CORE . DS .'app.php');

// Launching initialisation script
App::init();
