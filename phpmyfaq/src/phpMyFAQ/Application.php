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
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-24
 */

namespace phpMyFAQ;

use ErrorException;
use phpMyFAQ\Core\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

class Application
{
    private UrlMatcher $urlMatcher;
    private ControllerResolver $controllerResolver;

    public function __construct(private readonly ?ContainerInterface $container = null)
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
        $request = Request::createFromGlobals();
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);
        $this->handleRequest($routeCollection, $request, $requestContext);
    }

    public function setUrlMatcher($urlMatcher): void
    {
        $this->urlMatcher = $urlMatcher;
    }

    public function setControllerResolver(ControllerResolver $controllerResolver): void
    {
        $this->controllerResolver = $controllerResolver;
    }

    private function setLanguage(): string
    {
        if (!is_null($this->container)) {
            $configuration = $this->container->get('phpmyfaq.configuration');
            $language = $this->container->get('phpmyfaq.language');
            $currentLanguage = $language->setLanguage(
                $configuration->get('main.languageDetection'),
                $configuration->get('main.language')
            );

            require sprintf('%s/language_en.php', PMF_TRANSLATION_DIR);
            if (Language::isASupportedLanguage($currentLanguage)) {
                require sprintf('%s/language_%s.php', PMF_TRANSLATION_DIR, strtolower($currentLanguage));
            }

            $configuration->setLanguage($language);

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
            throw new Exception($exception->getMessage());
        }
    }

    private function handleRequest(RouteCollection $routeCollection, Request $request, RequestContext $context): void
    {
        if ( ! preg_match('/login|authenticate|logout|keep-alive|token|check/', $request->getRequestUri()) ) {
            $this->container->get('session')->set("lastRequestUri", $request->getRequestUri());
        }
        $urlMatcher = new UrlMatcher($routeCollection, $context);
        $this->setUrlMatcher($urlMatcher);
        $controllerResolver = new ControllerResolver();
        $this->setControllerResolver($controllerResolver);
        $argumentResolver = new ArgumentResolver();
        $response = new Response();

        try {
            $this->urlMatcher->setContext($context);
            $request->attributes->add($this->urlMatcher->match($request->getPathInfo()));
            $controller = $this->controllerResolver->getController($request);
            $arguments = $argumentResolver->getArguments($request, $controller);
            $response->setStatusCode(Response::HTTP_OK);
            $response = call_user_func_array($controller, $arguments);
        } catch (ResourceNotFoundException $exception) {
            $response = new Response(
                sprintf(
                    'Not Found: %s at line %d at %s',
                    $exception->getMessage(),
                    $exception->getLine(),
                    $exception->getFile()
                ),
                Response::HTTP_NOT_FOUND
            );
        } catch (UnauthorizedHttpException) {
            if (str_contains($urlMatcher->getContext()->getBaseUrl(), '/api')) {
                $response = new Response(
                    json_encode(['error' => 'Unauthorized access']),
                    Response::HTTP_UNAUTHORIZED,
                    ['Content-Type' => 'application/json']
                );
            } else {
                $response = new RedirectResponse('../login');
            }
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
