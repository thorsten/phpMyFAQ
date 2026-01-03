<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookies, posts and gets information and includes
 * the templates we need and sets all internal variables to the template
 * variables. That's all.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */

declare(strict_types=1);


use phpMyFAQ\Application;
use phpMyFAQ\Controller\Frontend\ErrorController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Environment;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

//
// Bootstrapping
//
try {
    require __DIR__ . '/src/Bootstrap.php';
} catch (DatabaseConnectionException $exception) {
    $errorMessage = Environment::isDebugMode() ? $exception->getMessage() : null;
    $response = ErrorController::renderBootstrapError($errorMessage);
    $response->send();
    exit(1);
}

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('./src/services.php');
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}

$routes = include PMF_SRC_DIR  . '/public-routes.php';

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}
