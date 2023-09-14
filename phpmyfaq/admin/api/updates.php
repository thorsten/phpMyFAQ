<?php

/**
 * Private phpMyFAQ Admin API: everything for the online update
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Jan Harms <model_railroader@gmx-topmail.de>
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-02
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

require '../../src/Bootstrap.php';

$faqConfig = Configuration::getConfigurationInstance();

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

$routes = new RouteCollection();

require '../../src/admin-routes.php';

$context = new RequestContext();
$context->fromRequest($request);
$matcher = new UrlMatcher($routes, $context);

$requestUri = $request->getPathInfo();

$generator = new UrlGenerator($routes, $context);

$parameters = $matcher->match($requestUri);

try {
    $parameters = $matcher->match($requestUri);
    list($controllerClass, $controllerMethod) = explode('::', $parameters['_class_and_method']);

    $action = new $controllerClass($faqConfig);
    $action->$controllerMethod(['request' => $request, 'generator' => $generator, 'parameters' => $parameters]);
} catch (ResourceNotFoundException $e) {
    $response->setStatusCode(Response::HTTP_NOT_FOUND);
    $response->setData($e->getMessage());
    $response->send();
} catch (Exception $e) {
    $response->setStatusCode(Response::HTTP_BAD_REQUEST);
    $response->setData($e->getMessage());
    $response->send();
}
