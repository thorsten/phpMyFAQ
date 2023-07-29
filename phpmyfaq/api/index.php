<?php

/**
 * phpMyFAQ REST API: api/v2.3/version
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
 * @since     2023-07-28
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

require '../src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

//
// Get language (default: english)
//
$language = new Language($faqConfig);
$currentLanguage = $language->setLanguageByAcceptLanguage();

//
// Set language
//
if (Language::isASupportedLanguage($currentLanguage)) {
    require PMF_TRANSLATION_DIR . '/language_' . $currentLanguage . '.php';
} else {
    require PMF_TRANSLATION_DIR . '/language_en.php';
}
$faqConfig->setLanguage($language);

//
// Create Request & Response
//
$response = new JsonResponse();
$request = Request::createFromGlobals();

//
// Set translation class
//
try {
    Translation::create()
        ->setLanguagesDir(PMF_TRANSLATION_DIR)
        ->setDefaultLanguage('en')
        ->setCurrentLanguage('en') // currently hardcoded
        ->setMultiByteLanguage();
} catch (Exception $e) {
    echo '<strong>Error:</strong> ' . $e->getMessage();
}

$request = Request::createFromGlobals();

$routes = include __DIR__ . '/../src/api-routes.php';

$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

$controllerResolver = new ControllerResolver();
$argumentResolver = new ArgumentResolver();

try {
    $request->attributes->add($matcher->match($request->getPathInfo()));

    $controller = $controllerResolver->getController($request);
    $arguments = $argumentResolver->getArguments($request, $controller);

    $response->setStatusCode(Response::HTTP_OK);
    $response = call_user_func_array($controller, $arguments);
} catch (ResourceNotFoundException $exception) {
    $response = new Response('Not Found', 404);
} catch (Exception $exception) {
    $response = new Response('An error occurred: ' . $exception->getMessage(), 500);
}

$response->send();
