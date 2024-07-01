<?php

/**
 * The main phpMyFAQ Setup.
 * This script checks the complete environment, writes the database connection
 * parameters into the file config/database.php and the configuration into the database.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Tom Rochester <tom.rochester@gmail.com>
 * @author    Johannes Schlüter <johannes@php.net>
 * @author    Uwe Pries <uwe.pries@digartis.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Florian Anderiasch <florian@phpmyfaq.de>
 * @copyright 2002-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-08-20
 */

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
$loader->addPsr4('phpMyFAQ\\', PMF_SRC_DIR . '/phpMyFAQ');
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
