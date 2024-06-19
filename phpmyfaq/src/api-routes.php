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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

use phpMyFAQ\Controller\Api\AttachmentController;
use phpMyFAQ\Controller\Api\BackupController;
use phpMyFAQ\Controller\Api\CategoryController;
use phpMyFAQ\Controller\Api\CommentController;
use phpMyFAQ\Controller\Api\FaqController;
use phpMyFAQ\Controller\Api\GroupController;
use phpMyFAQ\Controller\Api\LanguageController;
use phpMyFAQ\Controller\Api\LoginController;
use phpMyFAQ\Controller\Api\NewsController;
use phpMyFAQ\Controller\Api\OpenQuestionController;
use phpMyFAQ\Controller\Api\PdfController;
use phpMyFAQ\Controller\Api\QuestionController;
use phpMyFAQ\Controller\Api\RegistrationController;
use phpMyFAQ\Controller\Api\SearchController;
use phpMyFAQ\Controller\Api\SetupController;
use phpMyFAQ\Controller\Api\TagController;
use phpMyFAQ\Controller\Api\TitleController;
use phpMyFAQ\Controller\Api\VersionController;
use phpMyFAQ\Controller\Frontend\AutoCompleteController;
use phpMyFAQ\Controller\Frontend\BookmarkController;
use phpMyFAQ\Controller\Frontend\CaptchaController;
use phpMyFAQ\Controller\Frontend\CommentController as CommentFrontendController;
use phpMyFAQ\Controller\Frontend\ContactController;
use phpMyFAQ\Controller\Frontend\FaqController as FaqFrontendController;
use phpMyFAQ\Controller\Frontend\QuestionController as QuestionFrontendController;
use phpMyFAQ\Controller\Frontend\RegistrationController as RegistrationFrontendController;
use phpMyFAQ\Controller\Frontend\ShareController;
use phpMyFAQ\Controller\Frontend\UserController;
use phpMyFAQ\Controller\Frontend\VotingController;
use phpMyFAQ\System;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$apiVersion = System::getApiVersion();

$routes = new RouteCollection();


$routesConfig = [
    // Public REST API
    'api.attachments' => [
        'path' => "v{$apiVersion}/attachments/{recordId}",
        'controller' => [AttachmentController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.backup' => [
        'path' => "v{$apiVersion}/backup/{type}",
        'controller' => [BackupController::class, 'download'],
        'methods' => 'GET'
    ],
    'api.categories' => [
        'path' => "v{$apiVersion}/categories",
        'controller' => [CategoryController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.category' => [
        'path' => "v{$apiVersion}/category",
        'controller' => [CategoryController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.faq.create' => [
        'path' => "v{$apiVersion}/faq/create",
        'controller' => [FaqController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.faq.update' => [
        'path' => "v{$apiVersion}/faq/update",
        'controller' => [FaqController::class, 'update'],
        'methods' => 'PUT'
    ],
    'api.faq-by-id' => [
        'path' => "v{$apiVersion}/faq/{categoryId}/{faqId}",
        'controller' => [FaqController::class, 'getById'],
        'methods' => 'GET'
    ],
    'api.comments' => [
        'path' => "v{$apiVersion}/comments/{recordId}",
        'controller' => [CommentController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.faqs.by-tag-id' => [
        'path' => "v{$apiVersion}/faqs/tags/{tagId}",
        'controller' => [FaqController::class, 'getByTagId'],
        'methods' => 'GET'
    ],
    'api.faqs.latest' => [
        'path' => "v{$apiVersion}/faqs/latest",
        'controller' => [FaqController::class, 'getLatest'],
        'methods' => 'GET'
    ],
    'api.faqs.popular' => [
        'path' => "v{$apiVersion}/faqs/popular",
        'controller' => [FaqController::class, 'getPopular'],
        'methods' => 'GET'
    ],
    'api.faqs.trending' => [
        'path' => "v{$apiVersion}/faqs/trending",
        'controller' => [FaqController::class, 'getTrending'],
        'methods' => 'GET'
    ],
    'api.faqs.sticky' => [
        'path' => "v{$apiVersion}/faqs/sticky",
        'controller' => [FaqController::class, 'getSticky'],
        'methods' => 'GET'
    ],
    'api.faqs.by-category-id' => [
        'path' => "v{$apiVersion}/faqs/{categoryId}",
        'controller' => [FaqController::class, 'getByCategoryId'],
        'methods' => 'GET'
    ],
    'api.faqs' => [
        'path' => "v{$apiVersion}/faqs",
        'controller' => [FaqController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.groups' => [
        'path' => "v{$apiVersion}/groups",
        'controller' => [GroupController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.language' => [
        'path' => "v{$apiVersion}/language",
        'controller' => [LanguageController::class, 'index'],
        'methods' => 'GET'
    ],
    'api.login' => [
        'path' => "v{$apiVersion}/login",
        'controller' => [LoginController::class, 'login'],
        'methods' => 'POST'
    ],
    'api.news' => [
        'path' => "v{$apiVersion}/news",
        'controller' => [NewsController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.open-questions' => [
        'path' => "v{$apiVersion}/open-questions",
        'controller' => [OpenQuestionController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.pdf-by-id' => [
        'path' => "v{$apiVersion}/pdf/{categoryId}/{faqId}",
        'controller' => [PdfController::class, 'getById'],
        'methods' => 'GET'
    ],
    'api.question' => [
        'path' => "v{$apiVersion}/question",
        'controller' => [QuestionController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.register' => [
        'path' => "v{$apiVersion}/register",
        'controller' => [RegistrationController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.search' => [
        'path' => "v{$apiVersion}/search",
        'controller' => [SearchController::class, 'search'],
        'methods' => 'GET'
    ],
    'api.search.popular' => [
        'path' => "v{$apiVersion}/searches/popular",
        'controller' => [SearchController::class, 'popular'],
        'methods' => 'GET'
    ],
    'api.tags' => [
        'path' => "v{$apiVersion}/tags",
        'controller' => [TagController::class, 'list'],
        'methods' => 'GET'
    ],
    'api.title' => [
        'path' => "v{$apiVersion}/title",
        'controller' => [TitleController::class, 'index'],
        'methods' => 'GET'
    ],
    'api.version' => [
        'path' => "v{$apiVersion}/version",
        'controller' => [VersionController::class, 'index'],
        'methods' => 'GET'
    ],
    // Private REST API
    'api.private.autocomplete' => [
        'path' => 'autocomplete',
        'controller' => [AutoCompleteController::class, 'search'],
        'methods' => 'GET'
    ],
    'api.private.bookmark' => [
        'path' => 'bookmark/{bookmarkId}',
        'controller' => [BookmarkController::class, 'delete'],
        'methods' => 'DELETE'
    ],
    'api.private.captcha' => [
        'path' => 'captcha',
        'controller' => [CaptchaController::class, 'renderImage'],
        'methods' => 'GET'
    ],
    'api.private.contact' => [
        'path' => 'contact',
        'controller' => [ContactController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.comment' => [
        'path' => 'comment/create',
        'controller' => [CommentFrontendController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.faq.create' => [
        'path' => 'faq/create',
        'controller' => [FaqFrontendController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.question.create' => [
        'path' => 'question/create',
        'controller' => [QuestionFrontendController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.register' => [
        'path' => 'register',
        'controller' => [RegistrationFrontendController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.share' => [
        'path' => 'share',
        'controller' => [ShareController::class, 'create'],
        'methods' => 'POST'
    ],
    'api.private.user.password' => [
        'path' => 'user/password/update',
        'controller' => [UserController::class, 'updatePassword'],
        'methods' => 'PUT'
    ],
    'api.private.user.request-removal' => [
        'path' => 'user/request-removal',
        'controller' => [UserController::class, 'requestUserRemoval'],
        'methods' => 'POST'
    ],
    'api.private.user.remove-twofactor' => [
        'path' => 'user/remove-twofactor',
        'controller' => [UserController::class, 'removeTwofactorConfig'],
        'methods' => 'POST'
    ],
    'api.private.user.update' => [
        'path' => 'user/data/update',
        'controller' => [UserController::class, 'updateData'],
        'methods' => 'PUT'
    ],
    'api.private.voting' => [
        'path' => 'voting',
        'controller' => [VotingController::class, 'create'],
        'methods' => 'POST'
    ],
    // Setup REST API
    'api.private.setup.check' => [
        'path' => 'setup/check',
        'controller' => [SetupController::class, 'check'],
        'methods' => 'POST'
    ],
    'api.private.setup.backup' => [
        'path' => 'setup/backup',
        'controller' => [SetupController::class, 'backup'],
        'methods' => 'POST'
    ],
    'api.private.setup.update-database' => [
        'path' => 'setup/update-database',
        'controller' => [SetupController::class, 'updateDatabase'],
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
