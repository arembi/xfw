<?php

namespace Arembi\Xfw\Core;

/*
 * Every request MUST flow through this script
 * Includes the App class, which will coordinate the whole process
 * */

error_reporting(0);

// System directories
define('DS', DIRECTORY_SEPARATOR);
define('BASE', __DIR__);
define('ENGINE_DIR', BASE . DS . '..' . DS .'xfw_engine' );
define('CORE_DIR', ENGINE_DIR . DS . 'core');
define('SITES_DIR', BASE . DS . '..' . DS . 'xfw_sites');

// Loading application engine
require_once(CORE_DIR . DS .'app.php');

// Launching initialisation script
App::init();
