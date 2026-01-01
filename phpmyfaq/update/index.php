<?php

/**
 * The main phpMyFAQ Update.
 *
 * This script updates an existing phpMyFAQ installation to the latest version.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-05-31
 */

use phpMyFAQ\Application;
use phpMyFAQ\Controller\Frontend\SetupController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require '../src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('../src/services.php');
} catch (Exception $e) {
    echo $e->getMessage();
}

$routes = new RouteCollection();
$routes->add('public.update.index', new Route('/', ['_controller' => [SetupController::class, 'update']]));

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo sprintf(
        'An error occurred: %s at line %d at %s',
        $exception->getMessage(),
        $exception->getLine(),
        $exception->getFile()
    );
}
