<?php

/**
 * phpMyFAQ admin API routes
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-08
 */

use phpMyFAQ\Controller\Administration\AttachmentController;
use phpMyFAQ\Controller\Administration\CategoryController;
use phpMyFAQ\Controller\Administration\CommentController;
use phpMyFAQ\Controller\Administration\ConfigurationController;
use phpMyFAQ\Controller\Administration\ConfigurationTabController;
use phpMyFAQ\Controller\Administration\DashboardController;
use phpMyFAQ\Controller\Administration\ElasticsearchController;
use phpMyFAQ\Controller\Administration\ExportController;
use phpMyFAQ\Controller\Administration\FaqController;
use phpMyFAQ\Controller\Administration\FormController;
use phpMyFAQ\Controller\Administration\GlossaryController;
use phpMyFAQ\Controller\Administration\GroupController;
use phpMyFAQ\Controller\Administration\ImageController;
use phpMyFAQ\Controller\Administration\InstanceController;
use phpMyFAQ\Controller\Administration\MarkdownController;
use phpMyFAQ\Controller\Administration\NewsController;
use phpMyFAQ\Controller\Administration\QuestionController;
use phpMyFAQ\Controller\Administration\SearchController;
use phpMyFAQ\Controller\Administration\SessionController;
use phpMyFAQ\Controller\Administration\StatisticsController;
use phpMyFAQ\Controller\Administration\StopWordController;
use phpMyFAQ\Controller\Administration\TagController;
use phpMyFAQ\Controller\Administration\UpdateController;
use phpMyFAQ\Controller\Administration\UserController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    // Attachment API
    'admin.api.content.attachments' => [
        'path' => '/content/attachments',
        'controller' => [AttachmentController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.content.attachments.upload' => [
        'path' => '/content/attachments/upload',
        'controller' => [AttachmentController::class, 'upload'],
        'methods' => 'POST'
    ],
    // Category API
    'admin.api.category.delete' => [
        'path' => '/category/delete',
        'controller' => [CategoryController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.category.permissions' => [
        'path' => '/category/permissions/{categories}',
        'controller' => [CategoryController::class, 'permissions'],
        'methods' => 'GET'
    ],
    'admin.api.category.update-order' => [
        'path' => '/category/update-order',
        'controller' => [CategoryController::class, 'updateOrder'],
        'methods' => 'POST'
    ],
    'admin.api.category.translations' => [
        'path' => '/category/translations/{categoryId}',
        'controller' => [CategoryController::class, 'translations'],
        'methods' => 'GET'
    ],
    // Comment API
    'admin.api.content.comments' => [
        'path' => '/content/comments',
        'controller' => [CommentController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    // Configuration API
    'admin.api.configuration.faqs-sorting-key' => [
        'path' => '/configuration/faqs-sorting-key/{current}',
        'controller' => [ConfigurationTabController::class, 'faqsSortingKey'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.faqs-sorting-order' => [
        'path' => '/configuration/faqs-sorting-order/{current}',
        'controller' => [ConfigurationTabController::class, 'faqsSortingOrder'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.faqs-sorting-popular' => [
        'path' => '/configuration/faqs-sorting-popular/{current}',
        'controller' => [ConfigurationTabController::class, 'faqsSortingPopular'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.list' => [
        'path' => '/configuration/list/{mode}',
        'controller' => [ConfigurationTabController::class, 'list'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.permLevel' => [
        'path' => '/configuration/perm-level/{current}',
        'controller' => [ConfigurationTabController::class, 'permLevel'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.release-environment' => [
        'path' => '/configuration/release-environment/{current}',
        'controller' => [ConfigurationTabController::class, 'releaseEnvironment'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.save' => [
        'path' => '/configuration',
        'controller' => [ConfigurationTabController::class, 'save'],
        'methods' => 'POST'
    ],
    'admin.api.configuration.search-relevance' => [
        'path' => '/configuration/search-relevance/{current}',
        'controller' => [ConfigurationTabController::class, 'searchRelevance'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.seo-metatags' => [
        'path' => '/configuration/seo-metatags/{current}',
        'controller' => [ConfigurationTabController::class, 'seoMetaTags'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.send-test-mail' => [
        'path' => '/configuration/send-test-mail',
        'controller' => [ConfigurationController::class, 'sendTestMail'],
        'methods' => 'POST'
    ],
    'admin.api.configuration.translations' => [
        'path' => '/configuration/translations',
        'controller' => [ConfigurationTabController::class, 'translations'],
        'methods' => 'GET'
    ],
    'admin.api.configuration.templates' => [
        'path' => '/configuration/templates',
        'controller' => [ConfigurationTabController::class, 'templates'],
        'methods' => 'GET'
    ],
    // Glossary API
    'admin.api.glossary.add' => [
        'path' => '/glossary/add',
        'controller' => [GlossaryController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.api.glossary.delete' => [
        'path' => '/glossary/delete',
        'controller' => [GlossaryController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.glossary.update' => [
        'path' => '/glossary/update',
        'controller' => [GlossaryController::class, 'update'],
        'methods' => 'PUT'
    ],
    'admin.api.glossary' => [
        'path' => '/glossary/{glossaryId}',
        'controller' => [GlossaryController::class, 'fetch'],
        'methods' => 'GET'
    ],
    // Image API
    'admin.api.content.images' => [
        'path' => '/content/images',
        'controller' => [ImageController::class, 'upload'],
        'methods' => 'POST'
    ],
    // Instance API
    'admin.api.instance.add' => [
        'path' => '/instance/add',
        'controller' => [InstanceController::class, 'add'],
        'methods' => 'POST'
    ],
    'admin.api.instance.delete' => [
        'path' => '/instance/delete',
        'controller' => [InstanceController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    // Markdown API
    'admin.api.content.markdown' => [
        'path' => '/content/markdown',
        'controller' => [MarkdownController::class, 'renderMarkdown'],
        'methods' => 'POST'
    ],
    // Dashboard API
    'admin.api.dashboard.topten' => [
        'path' => '/dashboard/topten',
        'controller' => [DashboardController::class, 'topTen'],
        'methods' => 'GET'
    ],
    'admin.api.dashboard.versions' => [
        'path' => '/dashboard/versions',
        'controller' => [DashboardController::class, 'versions'],
        'methods' => 'GET'
    ],
    'admin.api.dashboard.visits' => [
        'path' => '/dashboard/visits',
        'controller' => [DashboardController::class, 'visits'],
        'methods' => 'GET'
    ],
    // Elasticsearch API
    'admin.api.elasticsearch.create' => [
        'path' => '/elasticsearch/create',
        'controller' => [ElasticsearchController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.api.elasticsearch.drop' => [
        'path' => '/elasticsearch/drop',
        'controller' => [ElasticsearchController::class, 'drop'],
        'methods' => 'POST'
    ],
    'admin.api.elasticsearch.import' => [
        'path' => '/elasticsearch/import',
        'controller' => [ElasticsearchController::class, 'import'],
        'methods' => 'POST'
    ],
    'admin.api.elasticsearch.statistics' => [
        'path' => '/elasticsearch/statistics',
        'controller' => [ElasticsearchController::class, 'statistics'],
        'methods' => 'GET'
    ],
    // Export API
    'admin.api.export.file' => [
        'path' => '/export/file',
        'controller' => [ExportController::class, 'exportFile'],
        'methods' => 'POST'
    ],
    'admin.api.export.report' => [
        'path' => '/export/report',
        'controller' => [ExportController::class, 'exportReport'],
        'methods' => 'POST'
    ],
    // FAQ API
    'admin.api.faq.activate' => [
        'path' => '/faq/activate',
        'controller' => [FaqController::class, 'activate'],
        'methods' => 'POST'
    ],
    'admin.api.faq.create' => [
        'path' => '/faq/create',
        'controller' => [FaqController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.api.faq.delete' => [
        'path' => '/faq/delete',
        'controller' => [FaqController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.faq.update' => [
        'path' => '/faq/update',
        'controller' => [FaqController::class, 'update'],
        'methods' => 'PUT'
    ],
    'admin.api.faq.permissions' => [
        'path' => '/faq/permissions/{faqId}',
        'controller' => [FaqController::class, 'listPermissions'],
        'methods' => 'GET'
    ],
    'admin.api.faq.search' => [
        'path' => '/faq/search',
        'controller' => [FaqController::class, 'search'],
        'methods' => 'POST'
    ],
    'admin.api.faq.sticky' => [
        'path' => '/faq/sticky',
        'controller' => [FaqController::class, 'sticky'],
        'methods' => 'POST'
    ],
    'admin.api.faqs' => [
        'path' => '/faqs/{categoryId}/{language}',
        'controller' => [FaqController::class, 'listByCategory'],
        'methods' => 'GET'
    ],
    'admin.api.faq.import' => [
        'path' => '/faq/import',
        'controller' => [FaqController::class, 'import'],
        'methods' => 'POST'
    ],
    'admin.api.faqs.sticky.order' => [
        'path' => '/faqs/sticky/order',
        'controller' => [FaqController::class, 'saveOrderOfStickyFaqs'],
        'methods' => 'POST'
    ],
    // Group API
    'admin.api.group.groups' => [
        'path' => '/group/groups',
        'controller' => [GroupController::class, 'listGroups'],
        'methods' => 'GET'
    ],
    'admin.api.group.members' => [
        'path' => '/group/members/{groupId}',
        'controller' => [GroupController::class, 'listMembers'],
        'methods' => 'GET'
    ],
    'admin.api.group.permissions' => [
        'path' => '/group/permissions/{groupId}',
        'controller' => [GroupController::class, 'listPermissions'],
        'methods' => 'GET'
    ],
    'admin.api.group.users' => [
        'path' => '/group/users',
        'controller' => [GroupController::class, 'listUsers'],
        'methods' => 'GET'
    ],
    'admin.api.group.data' => [
        'path' => '/group/data/{groupId}',
        'controller' => [GroupController::class, 'groupData'],
        'methods' => 'GET'
    ],
    // Question API
    'admin.api.question.delete' => [
        'path' => '/question/delete',
        'controller' => [QuestionController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    // Search API
    'admin.api.search.term' => [
        'path' => '/search/term',
        'controller' => [SearchController::class, 'deleteTerm'],
        'methods' => 'DELETE'
    ],
    // Stop word API
    'admin.api.stopwords' => [
        'path' => '/stopwords',
        'controller' => [StopWordController::class, 'list'],
        'methods' => 'GET'
    ],
    'admin.api.stopword.delete' => [
        'path' => '/stopword/delete',
        'controller' => [StopWordController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.stopword.save' => [
        'path' => '/stopword/save',
        'controller' => [StopWordController::class, 'save'],
        'methods' => 'POST'
    ],
    // Tag API
    'admin.api.content.tag' => [
        'path' => '/content/tag',
        'controller' => [TagController::class, 'update'],
        'methods' => 'PUT'
    ],
    'admin.api.content.tags' => [
        'path' => '/content/tags',
        'controller' => [TagController::class, 'search'],
        'methods' => 'GET'
    ],
    // Update API
    'admin.api.health-check' => [
        'path' => '/health-check',
        'controller' => [UpdateController::class, 'healthCheck'],
        'methods' => 'POST'
    ],
    'admin.api.versions' => [
        'path' => '/versions',
        'controller' => [UpdateController::class, 'versions'],
        'methods' => 'GET'
    ],
    'admin.api.update-check' => [
        'path' => '/update-check',
        'controller' => [UpdateController::class, 'updateCheck'],
        'methods' => 'GET'
    ],
    'admin.api.download-package' => [
        'path' => '/download-package/{versionNumber}',
        'controller' => [UpdateController::class, 'downloadPackage'],
        'methods' => 'POST'
    ],
    'admin.api.extract-package' => [
        'path' => '/extract-package',
        'controller' => [UpdateController::class, 'extractPackage'],
        'methods' => 'POST'
    ],
    'admin.api.create-temporary-backup' => [
        'path' => '/create-temporary-backup',
        'controller' => [UpdateController::class, 'createTemporaryBackup'],
        'methods' => 'POST'
    ],
    'admin.api.install-package' => [
        'path' => '/install-package',
        'controller' => [UpdateController::class, 'installPackage'],
        'methods' => 'POST'
    ],
    'admin.api.update-database' => [
        'path' => '/update-database',
        'controller' => [UpdateController::class, 'updateDatabase'],
        'methods' => 'POST'
    ],
    'admin.api.cleanup' => [
        'path' => '/cleanup',
        'controller' => [UpdateController::class, 'cleanUp'],
        'methods' => 'POST'
    ],
    // User API
    'admin.api.user.users' => [
        'path' => '/user/users',
        'controller' => [UserController::class, 'list'],
        'methods' => 'GET'
    ],
    'admin.api.user.add' => [
        'path' => '/user/add',
        'controller' => [UserController::class, 'addUser'],
        'methods' => 'POST'
    ],
    'admin.api.user.data' => [
        'path' => '/user/data/{userId}',
        'controller' => [UserController::class, 'userData'],
        'methods' => 'GET'
    ],
    'admin.api.user.delete' => [
        'path' => '/user/delete',
        'controller' => [UserController::class, 'deleteUser'],
        'methods' => 'DELETE'
    ],
    'admin.api.user.edit' => [
        'path' => '/user/edit',
        'controller' => [UserController::class, 'editUser'],
        'methods' => 'POST'
    ],
    'admin.api.user.update-rights' => [
        'path' => '/user/update-rights',
        'controller' => [UserController::class, 'updateUserRights'],
        'methods' => 'POST'
    ],
    'admin.api.user.permissions' => [
        'path' => '/user/permissions/{userId}',
        'controller' => [UserController::class, 'userPermissions'],
        'methods' => 'GET'
    ],
    'admin.api.user.activate' => [
        'path' => '/user/activate',
        'controller' => [UserController::class, 'activate'],
        'methods' => 'POST'
    ],
    'admin.api.user.overwrite-password' => [
        'path' => '/user/overwrite-password',
        'controller' => [UserController::class, 'overwritePassword'],
        'methods' => 'POST'
    ],
    // Session API
    'admin.api.session.export' => [
        'path' => '/session/export',
        'controller' => [SessionController::class, 'export'],
        'methods' => 'POST'
    ],
    // Statistics API
    'admin.api.statistics.adminlog.delete' => [
        'path' => '/statistics/admin-log',
        'controller' => [StatisticsController::class, 'deleteAdminLog'],
        'methods' => 'DELETE'
    ],
    'admin.api.statistics.search-terms.truncate' => [
        'path' => '/statistics/search-terms',
        'controller' => [StatisticsController::class, 'truncateSearchTerms'],
        'methods' => 'DELETE'
    ],
    // Forms API
    'admin.api.forms.activate' => [
        'path' => '/forms/activate',
        'controller' => [FormController::class, 'activateInput'],
        'methods' => 'POST'
    ],
    'admin.api.forms.required' => [
        'path' => '/forms/required',
        'controller' => [FormController::class, 'setInputAsRequired'],
        'methods' => 'POST'
    ],
    'admin.api.forms.translation-edit' => [
        'path' => '/forms/translation-edit',
        'controller' => [FormController::class, 'editTranslation'],
        'methods' => 'POST'
    ],
    'admin.api.forms.translation-delete' => [
        'path' => '/forms/translation-delete',
        'controller' => [FormController::class, 'deleteTranslation'],
        'methods' => 'POST'
    ],
    'admin.api.forms.translation-add' => [
        'path' => '/forms/translation-add',
        'controller' => [FormController::class, 'addTranslation'],
        'methods' => 'POST'
    ],
    // News API
    'admin.api.news.create' => [
        'path' => '/news/create',
        'controller' => [NewsController::class, 'create'],
        'methods' => 'POST'
    ],
    'admin.api.news.delete' => [
        'path' => '/news/delete',
        'controller' => [NewsController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'admin.api.news.update' => [
        'path' => '/news/update',
        'controller' => [NewsController::class, 'update'],
        'methods' => 'PUT'
    ],
    'admin.api.news.activate' => [
        'path' => '/news/activate',
        'controller' => [NewsController::class, 'activate'],
        'methods' => 'POST'
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
