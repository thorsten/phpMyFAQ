<?php

/**
 * phpMyFAQ REST API: api/v3.1/version
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-28
 */

use phpMyFAQ\Application;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

require dirname(__DIR__) . '/src/Bootstrap.php';

//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('../src/services.php');
} catch (Throwable $e) {
    error_log(sprintf(
        'phpMyFAQ API error: %s at line %d at %s',
        $e->getMessage(),
        $e->getLine(),
        $e->getFile(),
    ));
    (new JsonResponse(['error' => 'Internal Server Error'], Response::HTTP_INTERNAL_SERVER_ERROR))->send();
    exit;
}

$routes = include PMF_SRC_DIR . '/api-routes.php';

$app = new Application($container);
try {
    $app->run($routes);
} catch (Throwable $exception) {
    error_log(sprintf(
        'phpMyFAQ API error: %s at line %d at %s',
        $exception->getMessage(),
        $exception->getLine(),
        $exception->getFile(),
    ));
    (new JsonResponse(['error' => 'Internal Server Error'], Response::HTTP_INTERNAL_SERVER_ERROR))->send();
    exit;
}
