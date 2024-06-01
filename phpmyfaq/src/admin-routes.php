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

//
// Attachment API
//
$routes->add(
    'admin.api.content.attachments',
    new Route(
        '/content/attachments',
        [
            '_controller' => [AttachmentController::class, 'delete'],
            '_methods' => 'DELETE'
        ]
    )
);
$routes->add(
    'admin.api.content.attachments.upload',
    new Route(
        '/content/attachments/upload',
        [
            '_controller' => [AttachmentController::class, 'upload'],
            '_methods' => 'POST'
        ]
    )
);

//
// Category API
//
$routes->add(
    'admin.api.category.delete',
    new Route(
        '/category/delete',
        [
            '_controller' => [CategoryController::class, 'delete'],
            '_methods' => 'DELETE'
        ]
    )
);

$routes->add(
    'admin.api.category.permissions',
    new Route('/category/permissions/{categories}', ['_controller' => [CategoryController::class, 'permissions']])
);

$routes->add(
    'admin.api.category.update-order',
    new Route(
        '/category/update-order',
        [
            '_controller' => [CategoryController::class, 'updateOrder'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.category.translations',
    new Route('/category/translations/{categoryId}', ['_controller' => [CategoryController::class, 'translations']])
);
//
// Comment API
//
$routes->add(
    'admin.api.content.comments',
    new Route('/content/comments', ['_controller' => [CommentController::class, 'delete'], '_methods' => 'DELETE'])
);

//
// Configuration API
//
$routes->add(
    'admin.api.configuration.faqs-sorting-key',
    new Route(
        '/configuration/faqs-sorting-key/{current}',
        ['_controller' => [ConfigurationTabController::class, 'faqsSortingKey']]
    )
);

$routes->add(
    'admin.api.configuration.faqs-sorting-order',
    new Route(
        '/configuration/faqs-sorting-order/{current}',
        ['_controller' => [ConfigurationTabController::class, 'faqsSortingOrder']]
    )
);

$routes->add(
    'admin.api.configuration.faqs-sorting-popular',
    new Route(
        '/configuration/faqs-sorting-popular/{current}',
        ['_controller' => [ConfigurationTabController::class, 'faqsSortingPopular']]
    )
);

$routes->add(
    'admin.api.configuration.list',
    new Route('/configuration/list/{mode}', ['_controller' => [ConfigurationTabController::class, 'list']])
);

$routes->add(
    'admin.api.configuration.permLevel',
    new Route(
        '/configuration/perm-level/{current}',
        ['_controller' => [ConfigurationTabController::class, 'permLevel']]
    )
);

$routes->add(
    'admin.api.configuration.release-environment',
    new Route(
        '/configuration/release-environment/{current}',
        ['_controller' => [ConfigurationTabController::class, 'releaseEnvironment']]
    )
);

$routes->add(
    'admin.api.configuration.save',
    new Route(
        '/configuration',
        [
            '_controller' => [ConfigurationTabController::class, 'save'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.configuration.search-relevance',
    new Route(
        '/configuration/search-relevance/{current}',
        ['_controller' => [ConfigurationTabController::class, 'searchRelevance']]
    )
);

$routes->add(
    'admin.api.configuration.seo-metatags',
    new Route(
        '/configuration/seo-metatags/{current}',
        ['_controller' => [ConfigurationTabController::class, 'seoMetaTags']]
    )
);

$routes->add(
    'admin.api.configuration.send-test-mail',
    new Route(
        '/configuration/send-test-mail',
        [
            '_controller' => [ConfigurationController::class, 'sendTestMail'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.configuration.translations',
    new Route('/configuration/translations', ['_controller' => [ConfigurationTabController::class, 'translations']])
);

$routes->add(
    'admin.api.configuration.templates',
    new Route('/configuration/templates', ['_controller' => [ConfigurationTabController::class, 'templates']])
);

//
// Glossary API
//
$routes->add(
    'admin.api.glossary.add',
    new Route('/glossary/add', ['_controller' => [GlossaryController::class, 'create'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.glossary.delete',
    new Route('/glossary/delete', ['_controller' => [GlossaryController::class, 'delete'], '_methods' => 'DELETE'])
);

$routes->add(
    'admin.api.glossary.update',
    new Route('/glossary/update', ['_controller' => [GlossaryController::class, 'update'], '_methods' => 'PUT'])
);
$routes->add(
    'admin.api.glossary',
    new Route('/glossary/{glossaryId}', ['_controller' => [GlossaryController::class, 'fetch']])
);

//
// Image API
//
$routes->add(
    'admin.api.content.images',
    new Route('/content/images', ['_controller' => [ImageController::class, 'upload'], '_methods' => 'POST'])
);

//
// Instance API
//
$routes->add(
    'admin.api.instance.add',
    new Route('/instance/add', ['_controller' => [InstanceController::class, 'add'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.instance.delete',
    new Route('/instance/delete', ['_controller' => [InstanceController::class, 'delete'], '_methods' => 'DELETE'])
);

//
// Markdown API
//
$routes->add(
    'admin.api.content.markdown',
    new Route(
        '/content/markdown',
        ['_controller' => [MarkdownController::class, 'renderMarkdown'], '_methods' => 'POST']
    )
);

//
// Dashboard API
//
$routes->add(
    'admin.api.dashboard.topten',
    new Route('/dashboard/topten', ['_controller' => [DashboardController::class, 'topTen']])
);
$routes->add(
    'admin.api.dashboard.versions',
    new Route('/dashboard/versions', ['_controller' => [DashboardController::class, 'versions']])
);

$routes->add(
    'admin.api.dashboard.visits',
    new Route('/dashboard/visits', ['_controller' => [DashboardController::class, 'visits']])
);

//
// Elasticsearch API
//
$routes->add(
    'admin.api.elasticsearch.create',
    new Route('/elasticsearch/create', ['_controller' => [ElasticsearchController::class, 'create']])
);

$routes->add(
    'admin.api.elasticsearch.drop',
    new Route('/elasticsearch/drop', ['_controller' => [ElasticsearchController::class, 'drop']])
);

$routes->add(
    'admin.api.elasticsearch.import',
    new Route(
        '/elasticsearch/import',
        ['_controller' => [ElasticsearchController::class, 'import'], '_methods' => 'POST']
    )
);

$routes->add(
    'admin.api.elasticsearch.statistics',
    new Route('/elasticsearch/statistics', ['_controller' => [ElasticsearchController::class, 'statistics']])
);

//
// Export API
//
$routes->add(
    'admin.api.export.report',
    new Route('/export/report', ['_controller' => [ExportController::class, 'exportReport'], '_methods' => 'POST'])
);

//
// FAQ API
//
$routes->add(
    'admin.api.faq.activate',
    new Route('/faq/activate', ['_controller' => [FaqController::class, 'activate'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.faq.create',
    new Route('/faq/create', ['_controller' => [FaqController::class, 'create'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.faq.delete',
    new Route('/faq/delete', ['_controller' => [FaqController::class, 'delete'], '_methods' => 'DELETE'])
);

$routes->add(
    'admin.api.faq.update',
    new Route('/faq/update', ['_controller' => [FaqController::class, 'update'], '_methods' => 'PUT'])
);

$routes->add(
    'admin.api.faq.permissions',
    new Route('/faq/permissions/{faqId}', ['_controller' => [FaqController::class, 'listPermissions']])
);

$routes->add(
    'admin.api.faq.search',
    new Route('/faq/search', ['_controller' => [FaqController::class, 'search'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.faq.sticky',
    new Route('/faq/sticky', ['_controller' => [FaqController::class, 'sticky'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.faqs',
    new Route('/faqs/{categoryId}', ['_controller' => [FaqController::class, 'listByCategory']])
);
$routes->add(
    'admin.api.faq.import',
    new Route('/faq/import', ['_controller' => [FaqController::class, 'import'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.faqs.sticky.order',
    new Route(
        '/faqs/sticky/order',
        ['_controller' => [FaqController::class, 'saveOrderOfStickyFaqs'], '_methods' => 'POST']
    )
);

//
// Group API
//
$routes->add(
    'admin.api.group.groups',
    new Route('/group/groups', ['_controller' => [GroupController::class, 'listGroups']])
);

$routes->add(
    'admin.api.group.members',
    new Route('/group/members/{groupId}', ['_controller' => [GroupController::class, 'listMembers']])
);

$routes->add(
    'admin.api.group.permissions',
    new Route('/group/permissions/{groupId}', ['_controller' => [GroupController::class, 'listPermissions']])
);

$routes->add(
    'admin.api.group.users',
    new Route('/group/users', ['_controller' => [GroupController::class, 'listUsers']])
);

$routes->add(
    'admin.api.group.data',
    new Route('/group/data/{groupId}', ['_controller' => [GroupController::class, 'groupData']])
);

//
// Question API
//
$routes->add(
    'admin.api.question.delete',
    new Route('/question/delete', ['_controller' => [QuestionController::class, 'delete'], '_methods' => 'DELETE'])
);

//
// Search API
//
$routes->add(
    'admin.api.search.term',
    new Route('/search/term', ['_controller' => [SearchController::class, 'deleteTerm'], '_methods' => 'DELETE'])
);

//
// Stopword API
//
$routes->add(
    'admin.api.stopwords',
    new Route('/stopwords', ['_controller' => [StopWordController::class, 'list']])
);

$routes->add(
    'admin.api.stopword.delete',
    new Route('/stopword/delete', ['_controller' => [StopWordController::class, 'delete'], '_methods' => 'DELETE'])
);

$routes->add(
    'admin.api.stopword.save',
    new Route('/stopword/save', ['_controller' => [StopWordController::class, 'save'], '_methods' => 'POST'])
);

//
// Tag API
//
$routes->add(
    'admin.api.content.tag',
    new Route('/content/tag', ['_controller' => [TagController::class, 'update'], '_methods' => ['PUT']])
);

$routes->add(
    'admin.api.content.tags',
    new Route('/content/tags', ['_controller' => [TagController::class, 'search']])
);

//
// Update API
//
$routes->add(
    'admin.api.health-check',
    new Route('/health-check', ['_controller' => [UpdateController::class, 'healthCheck'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.versions',
    new Route('/versions', ['_controller' => [UpdateController::class, 'versions']])
);

$routes->add(
    'admin.api.update-check',
    new Route('/update-check', ['_controller' => [UpdateController::class, 'updateCheck']])
);

$routes->add(
    'admin.api.download-package',
    new Route(
        '/download-package/{versionNumber}',
        [
            '_controller' => [UpdateController::class, 'downloadPackage'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.extract-package',
    new Route(
        '/extract-package',
        [
            '_controller' => [UpdateController::class, 'extractPackage'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.create-temporary-backup',
    new Route(
        '/create-temporary-backup',
        [
            '_controller' => [UpdateController::class, 'createTemporaryBackup'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.install-package',
    new Route(
        '/install-package',
        [
            '_controller' => [UpdateController::class, 'installPackage'],
            '_methods' => 'POST'
        ]
    )
);

$routes->add(
    'admin.api.update-database',
    new Route(
        '/update-database',
        [
            '_controller' => [UpdateController::class, 'updateDatabase'],
            '_methods' => 'POST'
        ]
    )
);


$routes->add(
    'admin.api.cleanup',
    new Route(
        '/cleanup',
        [
            '_controller' => [UpdateController::class, 'cleanUp'],
            '_methods' => 'POST'
        ]
    )
);

//
// User API
//
$routes->add(
    'admin.api.user.users',
    new Route('/user/users', ['_controller' => [UserController::class, 'list']])
);

$routes->add(
    'admin.api.user.add',
    new Route('/user/add', ['_controller' => [UserController::class, 'addUser'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.user.data',
    new Route('/user/data/{userId}', ['_controller' => [UserController::class, 'userData']])
);

$routes->add(
    'admin.api.user.delete',
    new Route('/user/delete', ['_controller' => [UserController::class, 'deleteUser'], '_methods' => 'DELETE'])
);
$routes->add(
    'admin.api.user.edit',
    new Route('/user/edit', ['_controller' => [UserController::class, 'editUser'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.user.update-rights',
    new Route('/user/update-rights', ['_controller' => [UserController::class, 'updateUserRights'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.user.permissions',
    new Route('/user/permissions/{userId}', ['_controller' => [UserController::class, 'userPermissions']])
);

$routes->add(
    'admin.api.user.activate',
    new Route('/user/activate', ['_controller' => [UserController::class, 'activate'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.user.overwrite-password',
    new Route(
        '/user/overwrite-password',
        [
            '_controller' => [UserController::class, 'overwritePassword'],
            '_methods' => 'POST'
        ]
    )
);

//
// Export API
//
$routes->add(
    'admin.api.export.file',
    new Route('/export/file', ['_controller' => [ExportController::class, 'exportFile'], '_methods' => 'POST'])
);

$routes->add(
    'admin.api.export.report',
    new Route('/export/report', ['_controller' => [ExportController::class, 'exportReport'], '_methods' => 'POST'])
);

//
// Session API
//
$routes->add(
    'admin.api.session.export',
    new Route('/session/export', ['_controller' => [SessionController::class, 'export'], '_methods' => 'POST'])
);

//
// Statistics API
//
$routes->add(
    'admin.api.statistics.adminlog.delete',
    new Route(
        '/statistics/admin-log',
        ['_controller' => [StatisticsController::class, 'deleteAdminLog'], '_methods' => 'DELETE']
    )
);

$routes->add(
    'admin.api.statistics.search-terms.truncate',
    new Route(
        '/statistics/search-terms',
        ['_controller' => [StatisticsController::class, 'truncateSearchTerms'], '_methods' => 'DELETE']
    )
);

//
// Forms API
//
$routes->add(
    'admin.api.forms.activate',
    new Route('/forms/activate', ['_controller' => [FormController::class, 'activateInput'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.forms.required',
    new Route('/forms/required', ['_controller' => [FormController::class, 'setInputAsRequired'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.forms.translation-edit',
    new Route(
        '/forms/translation-edit',
        ['_controller' => [FormController::class, 'editTranslation'], '_methods' => 'POST']
    )
);
$routes->add(
    'admin.api.forms.translation-delete',
    new Route(
        '/forms/translation-delete',
        ['_controller' => [FormController::class, 'deleteTranslation'], '_methods' => 'POST']
    )
);
$routes->add(
    'admin.api.forms.translation-add',
    new Route(
        '/forms/translation-add',
        ['_controller' => [FormController::class, 'addTranslation'], '_methods' => 'POST']
    )
);

//
// News API
//
$routes->add(
    'admin.api.news.add',
    new Route('/news/add', ['_controller' => [NewsController::class, 'addNews'], '_methods' => 'POST'])
);
$routes->add(
    'admin.api.news.delete',
    new Route('/news/delete', ['_controller' => [NewsController::class, 'deleteNews'], '_methods' => 'DELETE'])
);
$routes->add(
    'admin.api.news.update',
    new Route('/news/update', ['_controller' => [NewsController::class, 'updateNews'], '_methods' => 'PUT'])
);
$routes->add(
    'admin.api.news.activate',
    new Route('/news/activate', ['_controller' => [NewsController::class, 'activateNews'], '_methods' => 'POST'])
);

return $routes;
