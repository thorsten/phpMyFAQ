<?php

/**
 * Private phpMyFAQ Admin API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-02
 */

use phpMyFAQ\Application;
use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Environment;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

try {
    require '../../src/Bootstrap.php';
} catch (DatabaseConnectionException $exception) {
    $errorMessage = Environment::isDebugMode()
        ? $exception->getMessage()
        : 'The database server is currently unavailable. Please try again later.';

    $problemDetails = [
        'type' => '/problems/database-unavailable',
        'title' => 'Database Connection Error',
        'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
        'detail' => $errorMessage,
        'instance' => $_SERVER['REQUEST_URI'] ?? '/api',
    ];

    $response = new JsonResponse(
        data: $problemDetails,
        status: Response::HTTP_INTERNAL_SERVER_ERROR,
        headers: ['Content-Type' => 'application/problem+json']
    );
    $response->send();
    exit(1);
}

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('../../src/services.php');
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}

$app = new Application($container);
$app->setAdminContext(true);
$app->setApiContext(true);
$app->routingContext = 'admin-api';
try {
    // Autoload routes from attributes (falls back to api-routes.php during migration)
    $app->run();
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}
