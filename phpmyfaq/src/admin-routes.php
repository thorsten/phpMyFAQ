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

use phpMyFAQ\Controller\Administration\AdminLogController;
use phpMyFAQ\Controller\Administration\AttachmentsController;
use phpMyFAQ\Controller\Administration\AuthenticationController;
use phpMyFAQ\Controller\Administration\BackupController;
use phpMyFAQ\Controller\Administration\CategoryController;
use phpMyFAQ\Controller\Administration\CommentsController;
use phpMyFAQ\Controller\Administration\ConfigurationController;
use phpMyFAQ\Controller\Administration\DashboardController;
use phpMyFAQ\Controller\Administration\ElasticsearchController;
use phpMyFAQ\Controller\Administration\ExportController;
use phpMyFAQ\Controller\Administration\FaqController;
use phpMyFAQ\Controller\Administration\FormsController;
use phpMyFAQ\Controller\Administration\GlossaryController;
use phpMyFAQ\Controller\Administration\GroupController;
use phpMyFAQ\Controller\Administration\ImportController;
use phpMyFAQ\Controller\Administration\InstanceController;
use phpMyFAQ\Controller\Administration\NewsController;
use phpMyFAQ\Controller\Administration\OpenQuestionsController;
use phpMyFAQ\Controller\Administration\PasswordChangeController;
use phpMyFAQ\Controller\Administration\RatingController;
use phpMyFAQ\Controller\Administration\ReportController;
use phpMyFAQ\Controller\Administration\SessionKeepAliveController;
use phpMyFAQ\Controller\Administration\StatisticsSearchController;
use phpMyFAQ\Controller\Administration\StatisticsSessionsController;
use phpMyFAQ\Controller\Administration\StickyFaqsController;
use phpMyFAQ\Controller\Administration\StopWordsController;
use phpMyFAQ\Controller\Administration\SystemInformationController;
use phpMyFAQ\Controller\Administration\TagController;
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
    'admin.auth.authenticate' => [
        'path' => '/authenticate',
        'controller' => [AuthenticationController::class, 'authenticate'],
        'methods' => 'POST'
    ],
    'admin.auth.check' => [
        'path' => '/check',
        'controller' => [AuthenticationController::class, 'check'],
        'methods' => 'POST'
    ],
    'admin.auth.login' => [
        'path' => '/login',
        'controller' => [AuthenticationController::class, 'login'],
        'methods' => 'GET'
    ],
    'admin.auth.logout' => [
        'path' => '/logout',
        'controller' => [AuthenticationController::class, 'logout'],
        'methods' => 'GET'
    ],
    'admin.auth.token' => [
        'path' => '/token',
        'controller' => [AuthenticationController::class, 'token'],
        'methods' => 'GET',

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
    'admin.category' => [
        'path' => '/category',
        'controller' => [CategoryController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.category.add' => [
        'path' => '/category/add',
        'controller' => [CategoryController::class, 'add'],
        'methods' => 'GET'
    ],
    'admin.category.add.child' => [
        'path' => '/category/add/{parentId}/{language}',
        'controller' => [CategoryController::class, 'addChild'],
        'methods' => 'GET'
    ],
    'admin.category.create' => [
        'path' => '/category/create',
        'controller' => [CategoryController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.category.edit' => [
        'path' => '/category/edit/{categoryId}',
        'controller' => [CategoryController::class, 'edit'],
        'methods' => 'GET'
    ],
    'admin.category.hierarchy' => [
        'path' => '/category/hierarchy',
        'controller' => [CategoryController::class, 'hierarchy'],
        'methods' => 'GET'
    ],
    'admin.category.translate' => [
        'path' => '/category/translate/{categoryId}',
        'controller' => [CategoryController::class, 'translate'],
        'methods' => 'POST'
    ],
    'admin.category.update' => [
        'path' => '/category/update',
        'controller' => [CategoryController::class, 'update'],
        'methods' => 'POST'
    ],
    'admin.content.sticky-faqs' => [
        'path' => '/sticky-faqs',
        'controller' => [StickyFaqsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.comments' => [
        'path' => '/comments',
        'controller' => [CommentsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.configuration' => [
        'path' => '/configuration',
        'controller' => [ConfigurationController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.dashboard' => [
        'path' => '/',
        'controller' => [DashboardController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.faq.add' => [
        'path' => '/faq/add',
        'controller' => [FaqController::class, 'add'],
        'methods' => 'GET'
    ],
    'admin.faq.answer' => [
        'path' => '/faq/answer/{questionId}/{faqLanguage}',
        'controller' => [FaqController::class, 'answer'],
        'methods' => 'GET'
    ],
    'admin.faq.copy' => [
        'path' => '/faq/copy/{faqId}/{faqLanguage}',
        'controller' => [FaqController::class, 'copy'],
        'methods' => 'GET'
    ],
    'admin.faq.edit' => [
        'path' => '/faq/edit/{faqId}/{faqLanguage}',
        'controller' => [FaqController::class, 'edit'],
        'methods' => 'GET'
    ],
    'admin.faq.translate' => [
        'path' => '/faq/translate/{faqId}/{faqLanguage}',
        'controller' => [FaqController::class, 'translate'],
        'methods' => 'GET'
    ],
    'admin.faqs' => [
        'path' => '/faqs',
        'controller' => [FaqController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.forms' => [
        'path' => '/forms',
        'controller' => [FormsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.forms.translate' => [
        'path' => '/forms/translate/{formId}/{inputId}',
        'controller' => [FormsController::class, 'translate'],
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
    'admin.glossary' => [
        'path' => '/glossary',
        'controller' => [GlossaryController::class, 'index'],
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
    'admin.news' => [
        'path' => '/news',
        'controller' => [NewsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.news.add' => [
        'path' => '/news/add',
        'controller' => [NewsController::class, 'add'],
        'methods' => 'GET'
    ],
    'admin.news.edit' => [
        'path' => '/news/edit/{newsId}',
        'controller' => [NewsController::class, 'edit'],
        'methods' => 'POST'
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
    'admin.questions' => [
        'path' => '/questions',
        'controller' => [OpenQuestionsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.session.keepalive' => [
        'path' => '/session-keep-alive',
        'controller' => [SessionKeepAliveController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.admin-log' => [
        'path' => '/statistics/admin-log',
        'controller' => [AdminLogController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.ratings' => [
        'path' => '/statistics/ratings',
        'controller' => [RatingController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.report' => [
        'path' => '/statistics/report',
        'controller' => [ReportController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.search' => [
        'path' => '/statistics/search',
        'controller' => [StatisticsSearchController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.sessions' => [
        'path' => '/statistics/sessions',
        'controller' => [StatisticsSessionsController::class, 'index'],
        'methods' => 'GET'
    ],
    'admin.statistics.sessions.day' => [
        'path' => '/statistics/sessions/{date}',
        'controller' => [StatisticsSessionsController::class, 'viewDay'],
        'methods' => 'POST, GET'
    ],
    'admin.statistics.session.id' => [
        'path' => '/statistics/session/{sessionId}',
        'controller' => [StatisticsSessionsController::class, 'viewSession'],
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
    'admin.tags' => [
        'path' => '/tags',
        'controller' => [TagController::class, 'index'],
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
