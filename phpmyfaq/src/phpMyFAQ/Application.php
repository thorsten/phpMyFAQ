<?php

/**
 * The main Application class
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
 * @since     2023-10-24
 */

namespace phpMyFAQ;

use ErrorException;
use phpMyFAQ\Core\Exception;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

readonly class Application
{
    public function __construct(private ?Configuration $configuration = null)
    {
    }

    /**
     * @throws Exception
     */
    public function run(RouteCollection $routeCollection): void
    {
        $currentLanguage = $this->setLanguage();
        $this->initializeTranslation($currentLanguage);
        Strings::init($currentLanguage);
        $this->handleRequest($routeCollection);
    }

    private function setLanguage(): string
    {
        if ($this->configuration) {
            $language = new Language($this->configuration);
            $currentLanguage = $language->setLanguageByAcceptLanguage();

            require sprintf('%s/language_en.php', PMF_TRANSLATION_DIR);
            if (Language::isASupportedLanguage($currentLanguage)) {
                require sprintf('%s/language_%s.php', PMF_TRANSLATION_DIR, $currentLanguage);
            }

            $this->configuration->setLanguage($language);

            return $currentLanguage;
        }

        return 'en';
    }

    /**
     * @throws Exception
     */
    private function initializeTranslation(string $currentLanguage): void
    {
        try {
            Translation::create()
                ->setLanguagesDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage('en')
                ->setCurrentLanguage($currentLanguage)
                ->setMultiByteLanguage();
        } catch (Exception $exception) {
            throw new Exception('x' . $exception->getMessage());
        }
    }

    private function handleRequest(RouteCollection $routeCollection): void
    {
        $request = Request::createFromGlobals();
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);

        $urlMatcher = new UrlMatcher($routeCollection, $requestContext);
        $controllerResolver = new ControllerResolver();
        $argumentResolver = new ArgumentResolver();
        $response = new Response();

        try {
            $request->attributes->add($urlMatcher->match($request->getPathInfo()));
            $controller = $controllerResolver->getController($request);
            $arguments = $argumentResolver->getArguments($request, $controller);
            $response->setStatusCode(Response::HTTP_OK);
            $response = call_user_func_array($controller, $arguments);
        } catch (ResourceNotFoundException) {
            $response = new Response('Not Found', Response::HTTP_NOT_FOUND);
        } catch (UnauthorizedHttpException) {
            $response = new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        } catch (BadRequestException $exception) {
            $response = new Response(
                sprintf(
                    'An error occurred: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile()
                ),
                Response::HTTP_BAD_REQUEST
            );
        } catch (ErrorException | Exception $exception) {
            $response = new Response(
                sprintf(
                    'An error occurred: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile()
                ),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $response->send();
    }
}
