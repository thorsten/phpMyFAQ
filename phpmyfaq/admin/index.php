<?php

/**
 * Entry point for the administration area
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Bastian Poettner <bastian@poettner.net>
 * @author    Meikel Katzengreis <meikel@katzengreis.com>
 * @author    Minoru TODA <todam@netjapan.co.jp>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2002-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2002-09-16
 */

use phpMyFAQ\Controller\Frontend\ErrorController;
use phpMyFAQ\Core\Exception\DatabaseConnectionException;
use phpMyFAQ\Environment;
use phpMyFAQ\Kernel;
use Symfony\Component\HttpFoundation\Request;

try {
    require dirname(__DIR__) . '/src/Bootstrap.php';
} catch (DatabaseConnectionException $databaseConnectionException) {
    $errorMessage = Environment::isDebugMode() ? $databaseConnectionException->getMessage() : null;
    $response = ErrorController::renderBootstrapError($errorMessage);
    $response->send();
    exit(1);
}

$kernel = new Kernel(
    routingContext: 'admin',
    debug: Environment::isDebugMode(),
);

$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
