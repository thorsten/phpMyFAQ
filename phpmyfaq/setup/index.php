<?php


use Composer\Autoload\ClassLoader;
use phpMyFAQ\Application;
use phpMyFAQ\Controller\Frontend\SetupController;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

define('PMF_ROOT_DIR', dirname(__FILE__, 2));

//
// The directory where the translations reside
//
define('PMF_TRANSLATION_DIR', dirname(__DIR__) . '/translations');

const PMF_SRC_DIR = PMF_ROOT_DIR . '/src';
const IS_VALID_PHPMYFAQ = null;

if (version_compare(PHP_VERSION, '8.1.0') < 0) {
    die('Sorry, but you need PHP 8.1.0 or later!');
}

set_time_limit(0);

if (!defined('DEBUG')) {
    define('DEBUG', true);
}

if (DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL | E_STRICT);
}

session_name('phpmyfaq-setup');
session_start();

require PMF_ROOT_DIR . '/src/libs/autoload.php';
require PMF_ROOT_DIR . '/src/constants.php';
require PMF_ROOT_DIR . '/content/core/config/constants.php';
require PMF_ROOT_DIR . '/content/core/config/constants_elasticsearch.php';

$loader = new ClassLoader();
$loader->add('phpMyFAQ', PMF_SRC_DIR);
$loader->register();

//
// Initialize static string wrapper
//
Strings::init();

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_LANGUAGE_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage('en')
        ->setMultiByteLanguage();
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

$routes = new RouteCollection();

$routeDefinitions = [
    'public.setup.index'   => ['/', SetupController::class, 'index'],
    'public.setup.install' => ['/install', SetupController::class, 'install'],
];

foreach ($routeDefinitions as $name => [$path, $controller, $action]) {
    $routes->add($name, new Route($path, ['_controller' => [$controller, $action]]));
}

$app = new Application();
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
