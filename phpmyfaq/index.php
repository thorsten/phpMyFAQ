<?php

/**
 * This is the main public frontend page of phpMyFAQ. It detects the browser's
 * language, gets and sets all cookies, posts and gets information and includes
 * the templates we need and sets all internal variables to the template
 * variables. That's all.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL wasn't distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2001-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2001-02-12
 */


use phpMyFAQ\Application;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Link;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

//
// Define the named constant used as a check by any included PHP file
//
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';


//
// Service Containers
//
$container = new ContainerBuilder();
$loader = new PhpFileLoader($container, new FileLocator(__DIR__));
try {
    $loader->load('./src/services.php');
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}

$routes = include PMF_SRC_DIR  . '/public-routes.php';

$app = new Application($container);
try {
    $app->run($routes);
} catch (Exception $exception) {
    echo sprintf('Error: %s at line %d at %s', $exception->getMessage(), $exception->getLine(), $exception->getFile());
}

/*

if (!isset($user)) {
    // Try to authenticate with cookie information
    try {
        $user = CurrentUser::getCurrentUser($faqConfig);
    } catch (Exception $e) {
        $faqConfig->getLogger()->error('Failed to authenticate via cookie: ' . $e->getMessage());
        $user = new CurrentUser($faqConfig);
    }
}



// Note: do not track internal calls
$internal = false;

if ($request->headers->get('user-agent') !== null) {
    $internal = str_starts_with($request->headers->get('user-agent'), 'phpMyFAQ%2F');
}

if (!$internal) {
    if (is_null($sidGet) && is_null($sidCookie)) {
        // Create a per-site unique SID
        $faqSession->userTracking('new_session', 0);
    } elseif (!is_null($sidCookie)) {
        $faqSession->checkSessionId($sidCookie, $request->getClientIp());
    } else {
        $faqSession->checkSessionId($sidGet, $request->getClientIp());
    }
}

//
// Is user tracking activated?
//
$sids = '';
if ($faqConfig->get('main.enableUserTracking')) {
    if ($faqSession->getCurrentSessionId() > 0) {
        $faqSession->setCookie(UserSession::COOKIE_NAME_SESSION_ID, $faqSession->getCurrentSessionId());
        if (is_null($sidCookie)) {
            $sids = sprintf('sid=%d&lang=%s&', $faqSession->getCurrentSessionId(), $faqLangCode);
        }
    } elseif (is_null($sidGet) || is_null($sidCookie)) {
        if (is_null($sidCookie) && !is_null($sidGet)) {
            $sids = sprintf('sid=%d&lang=%s&', $sidGet, $faqLangCode);
        }
    }
} else {
    $faqSession->setCookie(
        UserSession::COOKIE_NAME_SESSION_ID,
        $faqSession->getCurrentSessionId(),
        $request->server->get('REQUEST_TIME') + 3600,
    );
    $sids = sprintf('lang=%s&', $faqLangCode);
}

//
// Found a record ID?
//
$id = Filter::filterVar($request->query->get('id'), FILTER_VALIDATE_INT, 0);
if ($id !== 0) {
    $faq->getFaq($id);

    $seoEntity->setSeoType(SeoType::FAQ)->setReferenceId($id);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $metaDescription = str_replace('"', '', $seoData->getDescription() ?? '');
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        Strings::htmlentities($faqConfig->getDefaultUrl()),
        $sids,
        $category->getCategoryIdFromFaq($id),
        $id,
        $lang,
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->setTitle($faq->faqRecord['title']);
} else {
    $title = '';
    $metaDescription = str_replace('"', '', $faqConfig->get('seo.description'));
}

//
// found a solution ID?
//
$solutionId = Filter::filterVar($request->query->get('solution_id'), FILTER_VALIDATE_INT);
if ($solutionId) {
    $faqData = $faq->getIdFromSolutionId($solutionId);
    $id = $faqData['id'];
    $lang = $faqData['lang'];

    $seoEntity->setSeoType(SeoType::FAQ)->setReferenceId($id)->setReferenceLanguage($lang);
    $seoData = $seo->get($seoEntity);

    $title = $seoData->getTitle();
    $metaDescription = str_replace('"', '', $seoData->getDescription());
    $url = sprintf(
        '%sindex.php?%saction=faq&cat=%d&id=%d&artlang=%s',
        Strings::htmlentities($faqConfig->getDefaultUrl()),
        $sids,
        $faqData['category_id'],
        $id,
        $lang,
    );
    $faqLink = new Link($url, $faqConfig);
    $faqLink->setTitle(Strings::htmlentities($faqData['question']));
}

if (isset($cat) && $cat !== 0 && $id === 0 && $category->getCategoryName($cat) !== null) {
    $seoEntity->setSeoType(SeoType::CATEGORY)->setReferenceId($cat);
    $seoData = $seo->get($seoEntity);
    $title = $seoData->getTitle() ?? $category->getCategoryName($cat);
    $metaDescription = $seoData->getDescription() ?? $category->getCategoryDescription($cat);
}

//
// Handle 404 action with PageNotFoundController
//
if ('404' === $action) {
    $pageNotFoundController = new PageNotFoundController();
    $notFoundResponse = $pageNotFoundController->index($request);
    $notFoundResponse->send();
    exit();
}

//
// Check for 404 HTTP status code
//
if ($response->getStatusCode() === Response::HTTP_NOT_FOUND) {
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
}

$response->setCache([
    'must_revalidate' => false,
    'no_cache' => false,
    'no_store' => false,
    'no_transform' => false,
    'public' => true,
    'private' => false,
    'proxy_revalidate' => false,
    'max_age' => 600,
    's_maxage' => 600,
    'stale_if_error' => 86400,
    'stale_while_revalidate' => 60,
    'immutable' => true,
    'last_modified' => new DateTime(),
]);

//
// Avoid automatic downloads
// and prevent browsers from interpreting files as a different MIME type than what is specified
//
if ($action !== 'attachment') {
    $response->headers->set('Content-Disposition', 'inline');
}

$response->headers->set('X-Content-Type-Options', 'nosniff');

$response->send();
