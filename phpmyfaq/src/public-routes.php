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

use phpMyFAQ\Controller\Frontend\Api\SetupController;
use phpMyFAQ\Controller\Frontend\AttachmentController;
use phpMyFAQ\Controller\Frontend\ContactController;
use phpMyFAQ\Controller\Frontend\GlossaryController;
use phpMyFAQ\Controller\Frontend\LoginController;
use phpMyFAQ\Controller\Frontend\OverviewController;
use phpMyFAQ\Controller\Frontend\PageNotFoundController;
use phpMyFAQ\Controller\Frontend\PdfController;
use phpMyFAQ\Controller\Frontend\PrivacyController;
use phpMyFAQ\Controller\Frontend\SitemapController as FrontendSitemapController;
use phpMyFAQ\Controller\Frontend\UserController;
use phpMyFAQ\Controller\Frontend\WebAuthnController;
use phpMyFAQ\Controller\LlmsController;
use phpMyFAQ\Controller\RobotsController;
use phpMyFAQ\Controller\SitemapController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

$routesConfig = [
    'public.attachment' => [
        'path' => '/attachment/{attachmentId}',
        'controller' => [AttachmentController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.contact' => [
        'path' => '/contact.html',
        'controller' => [ContactController::class, 'index'],
        'methods' => 'GET|POST',
    ],
    'public.forgot-password' => [
        'path' => '/forgot-password',
        'controller' => [LoginController::class, 'forgotPassword'],
        'methods' => 'GET|POST',
    ],
    'public.glossary' => [
        'path' => '/glossary.html',
        'controller' => [GlossaryController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.login' => [
        'path' => '/login',
        'controller' => [LoginController::class, 'index'],
        'methods' => 'GET|POST',
    ],
    'public.overview' => [
        'path' => '/overview.html',
        'controller' => [OverviewController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.pdf.faq' => [
        'path' => '/pdf/{categoryId}/{faqId}/{faqLanguage}',
        'controller' => [PdfController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.privacy' => [
        'path' => '/privacy.html',
        'controller' => [PrivacyController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.user.register' => [
        'path' => '/user/register',
        'controller' => [UserController::class, 'register'],
        'methods' => 'GET',
    ],
    'public.user.request-removal' => [
        'path' => '/user/request-removal',
        'controller' => [UserController::class, 'requestRemoval'],
        'methods' => 'GET',
    ],
    'public.user.bookmarks' => [
        'path' => '/user/bookmarks',
        'controller' => [UserController::class, 'bookmarks'],
        'methods' => 'GET',
    ],
    'public.sitemap' => [
        'path' => '/sitemap/{letter}/{language}.html',
        'controller' => [FrontendSitemapController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.404' => [
        'path' => '/404.html',
        'controller' => [PageNotFoundController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.llms.txt' => [
        'path' => '/llms.txt',
        'controller' => [LlmsController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.robots.txt' => [
        'path' => '/robots.txt',
        'controller' => [RobotsController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.sitemap.xml' => [
        'path' => '/sitemap.xml',
        'controller' => [SitemapController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.webauthn.index' => [
        'path' => '/services/webauthn/',
        'controller' => [WebAuthnController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.update.index' => [
        'path' => '/update/',
        'controller' => [SetupController::class, 'update'],
        'methods' => 'POST',
    ],
];

foreach ($routesConfig as $name => $config) {
    $routes->add($name, new Route($config['path'], [
        '_controller' => $config['controller'],
        '_methods' => $config['methods'],
    ]));
}

return $routes;
