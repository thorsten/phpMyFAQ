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
use phpMyFAQ\Controller\Administration\BackupController;
use phpMyFAQ\Controller\Administration\ConfigurationController;
use phpMyFAQ\Controller\Administration\ElasticsearchController;
use phpMyFAQ\Controller\Administration\ExportController;
use phpMyFAQ\Controller\Administration\GroupController;
use phpMyFAQ\Controller\Administration\ImportController;
use phpMyFAQ\Controller\Administration\InstanceController;
use phpMyFAQ\Controller\Administration\PasswordChangeController;
use phpMyFAQ\Controller\Administration\RatingController;
use phpMyFAQ\Controller\Administration\SessionKeepAliveController;
use phpMyFAQ\Controller\Administration\StopWordsController;
use phpMyFAQ\Controller\Administration\SystemInformationController;
use phpMyFAQ\Controller\Administration\UpdateController;
use phpMyFAQ\Controller\Administration\UserController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    'admin.attachments' => [
        'path' => '/attachments',
        'controller' => [AttachmentsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.backup' => [
        'path' => '/backup',
        'controller' => [BackupController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.backup.export' => [
        'path' => '/backup/export/{type}',
        'controller' => [BackupController::class, 'export'],
        'methods' => 'GET'
    ],
    'admin.backup.restore' => [
        'path' => '/backup/restore',
        'controller' => [BackupController::class, 'restore'],
        'methods' => 'POST'
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
    'admin.export' => [
        'path' => '/export',
        'controller' => [ExportController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.group' => [
        'path' => '/group',
        'controller' => [GroupController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.group.add' => [
        'path' => '/group/add',
        'controller' => [GroupController::class, 'add'],
        'methods' => 'GET'
    ],
    'admin.group.create' => [
        'path' => '/group/create',
        'controller' => [GroupController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.group.confirm' => [
        'path' => '/group/confirm',
        'controller' => [GroupController::class, 'confirm'],
        'methods' => 'POST'
    ],
    'admin.group.delete' => [
        'path' => '/group/delete',
        'controller' => [GroupController::class, 'delete'],
        'methods' => 'POST'
    ],
    'admin.group.update' => [
        'path' => '/group/update',
        'controller' => [GroupController::class, 'update'],
        'methods' => 'POST'
    ],
    'admin.group.update.members' => [
        'path' => '/group/update/members',
        'controller' => [GroupController::class, 'updateMembers'],
        'methods' => 'POST'
    ],
    'admin.group.update.permissions' => [
        'path' => '/group/update/permissions',
        'controller' => [GroupController::class, 'updatePermissions'],
        'methods' => 'POST'
    ],
    'admin.import' => [
        'path' => '/import',
        'controller' => [ImportController::class, 'index'],
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
    'admin.password.change' => [
        'path' => '/password/change',
        'controller' => [PasswordChangeController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.password.update' => [
        'path' => '/password/update',
        'controller' => [PasswordChangeController::class, 'update'],
        'methods' => 'POST'
    ],
    'admin.session.keepalive' => [
        'path' => '/session-keep-alive',
        'controller' => [SessionKeepAliveController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.ratings' => [
        'path' => '/statistics/ratings',
        'controller' => [RatingController::class, 'index'],
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
    ],
    'admin.user' => [
        'path' => '/user',
        'controller' => [UserController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.user.edit' => [
        'path' => '/user/edit/{userId}',
        'controller' => [UserController::class, 'edit'],
        'methods' => 'GET'
    ],
    'admin.user.list' => [
        'path' => '/user/list',
        'controller' => [UserController::class, 'list'],
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
