<?php

/**
 * phpMyFAQ public routes
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

declare(strict_types=1);

use phpMyFAQ\Controller\FrontController;
use phpMyFAQ\Controller\WebAuthnController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    'public.index' => [
        'path' => '/',
        'controller' => [FrontController::class, 'handle'],
        'methods' => 'GET'
    ]
];

foreach ($routesConfig as $name => $config) {
    $routes->add(
        $name,
        new Route(
            $config['path'],
            [
                '_controller' => $config['controller'],
                '_methods' => $config['methods']
            ]
        )
    );
}

return $routes;
