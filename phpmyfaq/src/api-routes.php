<?php

/**
 * phpMyFAQ API routes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

use phpMyFAQ\Api\Controller\LanguageController;
use phpMyFAQ\Api\Controller\TitleController;
use phpMyFAQ\Api\Controller\VersionController;
use phpMyFAQ\System;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$apiVersion = System::getApiVersion();

$routes = new RouteCollection();
$routes->add(
    'api.version',
    new Route("v$apiVersion/version", ['_controller' => [VersionController::class, 'index']])
);
$routes->add(
    'api.language',
    new Route("v{$apiVersion}/language", ['_controller' => [LanguageController::class, 'index']])
);
$routes->add(
    'api.title',
    new Route("v{$apiVersion}/title", ['_controller' => [TitleController::class, 'index']])
);

return $routes;
