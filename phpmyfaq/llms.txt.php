<?php

/**
 * The dynamic llms.txt builder.
 *
 * The llms.txt protocol is described here:
 * https://llmstxt.org/
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-07-07
 */

use phpMyFAQ\Application;
use phpMyFAQ\Controller\LlmsController;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

require __DIR__ . '/src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('src/services.php');
} catch (Exception $exception) {
    echo $exception->getMessage();
}

$routes = new RouteCollection();
$routes->add('public.llms.txt', new Route('/llms.txt', ['_controller' => [LlmsController::class, 'index']]));

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo $exception->getMessage();
}
