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

use phpMyFAQ\Api\Controller\AttachmentController;
use phpMyFAQ\Api\Controller\CategoryController;
use phpMyFAQ\Api\Controller\CommentController;
use phpMyFAQ\Api\Controller\GroupController;
use phpMyFAQ\Api\Controller\LanguageController;
use phpMyFAQ\Api\Controller\LoginController;
use phpMyFAQ\Api\Controller\NewsController;
use phpMyFAQ\Api\Controller\OpenQuestionController;
use phpMyFAQ\Api\Controller\SearchController;
use phpMyFAQ\Api\Controller\TagController;
use phpMyFAQ\Api\Controller\TitleController;
use phpMyFAQ\Api\Controller\VersionController;
use phpMyFAQ\Controller\AutoCompleteController;
use phpMyFAQ\Controller\BookmarkController;
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
    'api.categories',
    new Route("v{$apiVersion}/categories", ['_controller' => [CategoryController::class, 'list']])
);
$routes->add(
    'api.comments',
    new Route("v{$apiVersion}/comments/{recordId}", ['_controller' => [CommentController::class, 'list']])
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

// Private REST API
$routes->add(
    'api.autocomplete',
    new Route('autocomplete', ['_controller' => [AutoCompleteController::class, 'search']])
);
$routes->add(
    'api.bookmark',
    new Route('bookmark', ['_controller' => [BookmarkController::class, 'delete'], '_methods' => 'DELETE'])
);

return $routes;
