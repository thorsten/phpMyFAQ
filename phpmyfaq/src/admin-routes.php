<?php

/**
 * phpMyFAQ admin routes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-11-22
 */

use phpMyFAQ\Controller\Administration\AttachmentsController;
use phpMyFAQ\Controller\Administration\ConfigurationController;
use phpMyFAQ\Controller\Administration\ElasticsearchController;
use phpMyFAQ\Controller\Administration\InstanceController;
use phpMyFAQ\Controller\Administration\StopWordsController;
use phpMyFAQ\Controller\Administration\SystemInformationController;
use phpMyFAQ\Controller\Administration\UpdateController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    'admin.attachments' => [
        'path' => '/attachments',
        'controller' => [AttachmentsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.configuration' => [
        'path' => '/configuration',
        'controller' => [ConfigurationController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.elasticsearch' => [
        'path' => '/elasticsearch',
        'controller' => [ElasticsearchController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.instance.edit' => [
        'path' => '/instance/edit/{id}',
        'controller' => [InstanceController::class, 'edit'],
        'methods' => 'GET'
    ],
    'admin.instance.update' => [
        'path' => '/instance/update',
        'controller' => [InstanceController::class, 'update'],
        'methods' => 'POST'
    ],
    'admin.instances' => [
        'path' => '/instances',
        'controller' => [InstanceController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.stopwords' => [
        'path' => '/stopwords',
        'controller' => [StopwordsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.system' => [
        'path' => '/system',
        'controller' => [SystemInformationController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.update' => [
        'path' => '/update',
        'controller' => [UpdateController::class, 'index'],
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
