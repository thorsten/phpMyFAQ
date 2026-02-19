<?php

/**
 * phpMyFAQ REST API: api/v3.2
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

declare(strict_types=1);

use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Environment;
use phpMyFAQ\Kernel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

try {
    require '../src/Bootstrap.php';
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

$kernel = new Kernel(
    routingContext: 'api',
    debug: Environment::isDebugMode(),
);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
