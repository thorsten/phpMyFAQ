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

use phpMyFAQ\Controller\Frontend\AttachmentController;
use phpMyFAQ\Controller\Frontend\AuthenticationController;
use phpMyFAQ\Controller\Frontend\CategoryController;
use phpMyFAQ\Controller\Frontend\ContactController;
use phpMyFAQ\Controller\Frontend\CookiePolicyController;
use phpMyFAQ\Controller\Frontend\CustomPageController;
use phpMyFAQ\Controller\Frontend\FaqController;
use phpMyFAQ\Controller\Frontend\GlossaryController;
use phpMyFAQ\Controller\Frontend\ImprintController;
use phpMyFAQ\Controller\Frontend\NewsController;
use phpMyFAQ\Controller\Frontend\OverviewController;
use phpMyFAQ\Controller\Frontend\PageNotFoundController;
use phpMyFAQ\Controller\Frontend\PdfController;
use phpMyFAQ\Controller\Frontend\PrivacyController;
use phpMyFAQ\Controller\Frontend\QuestionsController;
use phpMyFAQ\Controller\Frontend\SearchController;
use phpMyFAQ\Controller\Frontend\SetupController;
use phpMyFAQ\Controller\Frontend\SitemapController as FrontendSitemapController;
use phpMyFAQ\Controller\Frontend\StartpageController;
use phpMyFAQ\Controller\Frontend\TermsController;
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
    'public.category.show' => [
        'path' => '/category/{categoryId}/{slug}.html',
        'controller' => [CategoryController::class, 'show'],
        'methods' => 'GET',
    ],
    'public.category.showAll' => [
        'path' => '/show-categories.html',
        'controller' => [CategoryController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.contact' => [
        'path' => '/contact.html',
        'controller' => [ContactController::class, 'index'],
        'methods' => 'GET|POST',
    ],
    'public.faq.add' => [
        'path' => '/add-faq.html',
        'controller' => [FaqController::class, 'add'],
        'methods' => 'GET',
    ],
    'public.faq.solution' => [
        'path' => '/solution_id_{solutionId}.html',
        'controller' => [FaqController::class, 'solution'],
        'methods' => 'GET',
    ],
    'public.faq.show' => [
        'path' => '/content/{categoryId}/{faqId}/{language}/{slug}.html',
        'controller' => [FaqController::class, 'show'],
        'methods' => 'GET',
    ],
    'public.faq.redirect' => [
        'path' => '/content/{faqId}/{faqLang}',
        'controller' => [FaqController::class, 'contentRedirect'],
        'methods' => 'GET',
    ],
    'public.forgot-password' => [
        'path' => '/forgot-password',
        'controller' => [AuthenticationController::class, 'forgotPassword'],
        'methods' => 'GET|POST',
    ],
    'public.glossary' => [
        'path' => '/glossary.html',
        'controller' => [GlossaryController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.auth.login' => [
        'path' => '/login',
        'controller' => [AuthenticationController::class, 'login'],
        'methods' => 'GET',
    ],
    'public.auth.authenticate' => [
        'path' => '/authenticate',
        'controller' => [AuthenticationController::class, 'authenticate'],
        'methods' => 'POST',
    ],
    'public.auth.check' => [
        'path' => '/check',
        'controller' => [AuthenticationController::class, 'check'],
        'methods' => 'POST',
    ],
    'public.auth.token' => [
        'path' => '/token',
        'controller' => [AuthenticationController::class, 'token'],
        'methods' => 'POST',
    ],
    'public.auth.logout' => [
        'path' => '/logout',
        'controller' => [AuthenticationController::class, 'logout'],
        'methods' => 'GET',
    ],
    'public.news' => [
        'path' => '/news/{newsId}/{newsLang}/{slug}.html',
        'controller' => [NewsController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.page' => [
        'path' => '/page/{slug}.html',
        'controller' => [CustomPageController::class, 'show'],
        'methods' => 'GET',
    ],
    'public.open-questions' => [
        'path' => '/open-questions.html',
        'controller' => [QuestionsController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.question.ask' => [
        'path' => '/add-question.html',
        'controller' => [QuestionsController::class, 'ask'],
        'methods' => 'GET',
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
    'public.terms' => [
        'path' => '/terms.html',
        'controller' => [TermsController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.imprint' => [
        'path' => '/imprint.html',
        'controller' => [ImprintController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.cookies' => [
        'path' => '/cookies.html',
        'controller' => [CookiePolicyController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.index' => [
        'path' => '/',
        'controller' => [StartpageController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.search' => [
        'path' => '/search.html',
        'controller' => [SearchController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.tags.paginated' => [
        'path' => '/tags/{tagId}/{page}/{slug}.html',
        'controller' => [SearchController::class, 'tagsPaginated'],
        'methods' => 'GET',
    ],
    'public.tags' => [
        'path' => '/tags/{tagId}/{slug}.html',
        'controller' => [SearchController::class, 'tags'],
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
    'public.user.ucp' => [
        'path' => '/user/ucp',
        'controller' => [UserController::class, 'ucp'],
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
    'public.sitemap.gz' => [
        'path' => '/sitemap.gz',
        'controller' => [SitemapController::class, 'sitemapGz'],
        'methods' => 'GET',
    ],
    'public.sitemap.xml.gz' => [
        'path' => '/sitemap.xml.gz',
        'controller' => [SitemapController::class, 'sitemapXmlGz'],
        'methods' => 'GET',
    ],
    'public.webauthn.index' => [
        'path' => '/services/webauthn/',
        'controller' => [WebAuthnController::class, 'index'],
        'methods' => 'GET',
    ],
    'public.update.index' => [
        'path' => '/update',
        'controller' => [SetupController::class, 'update'],
        'methods' => 'GET',
    ],
];

foreach ($routesConfig as $name => $config) {
    $routes->add($name, new Route($config['path'], [
        '_controller' => $config['controller'],
        '_methods' => $config['methods'],
    ]));
}

return $routes;
