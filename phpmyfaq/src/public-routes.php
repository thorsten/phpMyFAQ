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

use phpMyFAQ\Controller\ContactController;
use phpMyFAQ\Controller\LlmsController;
use phpMyFAQ\Controller\PageNotFoundController;
use phpMyFAQ\Controller\RobotsController;
use phpMyFAQ\Controller\SitemapController;
use phpMyFAQ\Controller\WebAuthnController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    'public.contact' => [
        'path' => '/contact.html',
        'controller' => [ContactController::class, 'index'],
        'methods' => 'GET|POST'
    ],
    'public.404' => [
        'path' => '/404.html',
        'controller' => [PageNotFoundController::class, 'index'],
        'methods' => 'GET'
    ],
    'public.llms.txt' => [
        'path' => '/llms.txt',
        'controller' => [LlmsController::class, 'index'],
        'methods' => 'GET'
    ],
    'public.robots.txt' => [
        'path' => '/robots.txt',
        'controller' => [RobotsController::class, 'index'],
        'methods' => 'GET'
    ],
    'public.sitemap.xml' => [
        'path' => '/sitemap.xml',
        'controller' => [SitemapController::class, 'index'],
        'methods' => 'GET'
    ],
    'public.webauthn.index' => [
        'path' => '/services/webauthn/',
        'controller' => [WebAuthnController::class, 'index'],
        'methods' => 'GET'
    ],
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
