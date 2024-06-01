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

// Public REST API
$routes->add(
    'api.attachments',
    new Route("v{$apiVersion}/attachments/{recordId}", ['_controller' => [AttachmentController::class, 'list']])
);
$routes->add(
    'api.backup',
    new Route("v{$apiVersion}/backup/{type}", ['_controller' => [BackupController::class, 'download']])
);
$routes->add(
    'api.categories',
    new Route("v{$apiVersion}/categories", ['_controller' => [CategoryController::class, 'list']])
);
$routes->add(
    'api.category',
    new Route("v{$apiVersion}/category", ['_controller' => [CategoryController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.faq.create',
    new Route("v{$apiVersion}/faq/create", ['_controller' => [FaqController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.faq.update',
    new Route("v{$apiVersion}/faq/update", ['_controller' => [FaqController::class, 'update'], '_methods' => 'PUT'])
);
$routes->add(
    'api.faq-by-id',
    new Route("v{$apiVersion}/faq/{categoryId}/{faqId}", ['_controller' => [FaqController::class, 'getById']])
);
$routes->add(
    'api.comments',
    new Route("v{$apiVersion}/comments/{recordId}", ['_controller' => [CommentController::class, 'list']])
);
$routes->add(
    'api.faqs.by-tag-id',
    new Route("v{$apiVersion}/faqs/tags/{tagId}", ['_controller' => [FaqController::class, 'getByTagId']])
);
$routes->add(
    'api.faqs.latest',
    new Route("v{$apiVersion}/faqs/latest", ['_controller' => [FaqController::class, 'getLatest']])
);
$routes->add(
    'api.faqs.popular',
    new Route("v{$apiVersion}/faqs/popular", ['_controller' => [FaqController::class, 'getPopular']])
);
$routes->add(
    'api.faqs.sticky',
    new Route("v{$apiVersion}/faqs/sticky", ['_controller' => [FaqController::class, 'getSticky']])
);
$routes->add(
    'api.faqs.by-category-id',
    new Route("v{$apiVersion}/faqs/{categoryId}", ['_controller' => [FaqController::class, 'getByCategoryId']])
);
$routes->add(
    'api.faqs',
    new Route("v{$apiVersion}/faqs", ['_controller' => [FaqController::class, 'list']])
);
$routes->add(
    'api.groups',
    new Route("v{$apiVersion}/groups", ['_controller' => [GroupController::class, 'list']])
);
$routes->add(
    'api.language',
    new Route("v{$apiVersion}/language", ['_controller' => [LanguageController::class, 'index']])
);
$routes->add(
    'api.login',
    new Route("v{$apiVersion}/login", ['_controller' => [LoginController::class, 'login'], '_methods' => 'POST'])
);
$routes->add(
    'api.news',
    new Route("v{$apiVersion}/news", ['_controller' => [NewsController::class, 'list']])
);
$routes->add(
    'api.open-questions',
    new Route("v{$apiVersion}/open-questions", ['_controller' => [OpenQuestionController::class, 'list']])
);
$routes->add(
    'api.pdf-by-id',
    new Route("v{$apiVersion}/pdf/{categoryId}/{faqId}", ['_controller' => [PdfController::class, 'getById']])
);
$routes->add(
    'api.question',
    new Route("v{$apiVersion}/question", ['_controller' => [QuestionController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.register',
    new Route(
        "v{$apiVersion}/register",
        [
            '_controller' => [RegistrationController::class, 'create'],
            '_methods' => 'POST'
        ]
    )
);
$routes->add(
    'api.search',
    new Route("v{$apiVersion}/search", ['_controller' => [SearchController::class, 'search']])
);
$routes->add(
    'api.search.popular',
    new Route("v{$apiVersion}/searches/popular", ['_controller' => [SearchController::class, 'popular']])
);
$routes->add(
    'api.tags',
    new Route("v{$apiVersion}/tags", ['_controller' => [TagController::class, 'list']])
);
$routes->add(
    'api.title',
    new Route("v{$apiVersion}/title", ['_controller' => [TitleController::class, 'index']])
);
$routes->add(
    'api.version',
    new Route("v$apiVersion/version", ['_controller' => [VersionController::class, 'index']])
);

//
// Private REST API
//
$routes->add(
    'api.private.autocomplete',
    new Route('autocomplete', ['_controller' => [AutoCompleteController::class, 'search']])
);
$routes->add(
    'api.private.bookmark',
    new Route('bookmark/{bookmarkId}', ['_controller' => [BookmarkController::class, 'delete'], '_methods' => 'DELETE'])
);
$routes->add(
    'api.private.captcha',
    new Route('captcha', ['_controller' => [CaptchaController::class, 'renderImage']])
);
$routes->add(
    'api.private.contact',
    new Route('contact', ['_controller' => [ContactController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.comment',
    new Route('comment/create', ['_controller' => [CommentFrontendController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.faq.create',
    new Route('faq/create', ['_controller' => [FaqFrontendController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.question.create',
    new Route('question/create', ['_controller' => [QuestionFrontendController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.register',
    new Route('register', ['_controller' => [RegistrationFrontendController::class, 'create']])
);
$routes->add(
    'api.private.share',
    new Route('share', ['_controller' => [ShareController::class, 'create'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.user.password',
    new Route('user/password/update', ['_controller' => [UserController::class, 'updatePassword'], '_methods' => 'PUT'])
);
$routes->add(
    'api.private.user.request-removal',
    new Route(
        'user/request-removal',
        ['_controller' => [UserController::class, 'requestUserRemoval'], '_methods' => 'POST']
    )
);
$routes->add(
    'api.private.user.update',
    new Route('user/data/update', ['_controller' => [UserController::class, 'updateData'], '_methods' => 'PUT'])
);
$routes->add(
    'api.private.voting',
    new Route('voting', ['_controller' => [VotingController::class, 'create'], '_methods' => 'POST'])
);

//
// Setup REST API
//
$routes->add(
    'api.private.setup.check',
    new Route('setup/check', ['_controller' => [SetupController::class, 'check'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.setup.backup',
    new Route('setup/backup', ['_controller' => [SetupController::class, 'backup'], '_methods' => 'POST'])
);
$routes->add(
    'api.private.setup.update-database',
    new Route(
        'setup/update-database',
        [
            '_controller' => [ SetupController::class, 'updateDatabase' ],
            '_methods' => 'POST'
        ]
    )
);

return $routes;
