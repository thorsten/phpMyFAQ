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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-24
 */

declare(strict_types=1);

namespace phpMyFAQ;

use phpMyFAQ\Controller\Exception\ForbiddenException;
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
use Throwable;

class Application
{
    private UrlMatcher $urlMatcher;

    private ControllerResolver $controllerResolver;

    private bool $isApiContext = false;

    public function __construct(
        private readonly ?ContainerInterface $container = null,
    ) {
    }

    /**
     * @throws Exception
     */
    public function run(RouteCollection $routeCollection, ?Request $request = null): void
    {
        $currentLanguage = $this->setLanguage();
        $this->initializeTranslation($currentLanguage);
        Strings::init($currentLanguage);
        $request = $request ?? Request::createFromGlobals();
        $requestContext = new RequestContext();
        $requestContext->fromRequest($request);
        $this->handleRequest($routeCollection, $request, $requestContext);
    }

    public function setUrlMatcher(UrlMatcher $urlMatcher): void
    {
        $this->urlMatcher = $urlMatcher;
    }

    public function setControllerResolver(ControllerResolver $controllerResolver): void
    {
        $this->controllerResolver = $controllerResolver;
    }

    public function setApiContext(bool $isApiContext): void
    {
        $this->isApiContext = $isApiContext;
    }

    private function setLanguage(): string
    {
        if (!is_null($this->container)) {
            $configuration = $this->container->get(id: 'phpmyfaq.configuration');
            $language = $this->container->get(id: 'phpmyfaq.language');

            $detect = (bool) $configuration->get(item: 'main.languageDetection');
            $configLang = $configuration->get(item: 'main.language');

            $currentLanguage = $detect
                ? $language->setLanguageWithDetection($configLang)
                : $language->setLanguageFromConfiguration($configLang);

            require PMF_TRANSLATION_DIR . '/language_en.php';
            if (Language::isASupportedLanguage($currentLanguage)) {
                require PMF_TRANSLATION_DIR . '/language_' . strtolower($currentLanguage) . '.php';
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
                ->setTranslationsDir(PMF_TRANSLATION_DIR)
                ->setDefaultLanguage(defaultLanguage: 'en')
                ->setCurrentLanguage($currentLanguage)
                ->setMultiByteLanguage();
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
    }

    private function handleRequest(
        RouteCollection $routeCollection,
        Request $request,
        RequestContext $requestContext,
    ): void {
        $urlMatcher = new UrlMatcher($routeCollection, $requestContext);
        $this->setUrlMatcher($urlMatcher);
        $controllerResolver = new ControllerResolver();
        $this->setControllerResolver($controllerResolver);
        $argumentResolver = new ArgumentResolver();
        $response = new Response();

        try {
            $this->urlMatcher->setContext($requestContext);
            $request->attributes->add($this->urlMatcher->match($request->getPathInfo()));
            $controller = $this->controllerResolver->getController($request);
            $arguments = $argumentResolver->getArguments($request, $controller);
            $response->setStatusCode(Response::HTTP_OK);
            $response = call_user_func_array($controller, $arguments);
        } catch (ResourceNotFoundException $exception) {
            // For API requests, return simple text/JSON response
            if ($this->isApiContext) {
                $message = Environment::isDebugMode()
                    ? $this->formatExceptionMessage(
                        template: 'Not Found: :message at line :line at :file',
                        exception: $exception,
                    )
                    : 'Not Found';
                $response = new Response(content: $message, status: Response::HTTP_NOT_FOUND);
            } else {
                // For web requests, forward to the PageNotFoundController
                try {
                    $request->attributes->set('_route', 'public.404');
                    $request->attributes->set(
                        '_controller',
                        'phpMyFAQ\Controller\Frontend\PageNotFoundController::index',
                    );
                    $controller = $this->controllerResolver->getController($request);
                    $arguments = $argumentResolver->getArguments($request, $controller);
                    $response = call_user_func_array($controller, $arguments);
                } catch (Throwable $e) {
                    // Fallback if the controller fails
                    $message = Environment::isDebugMode()
                        ? $this->formatExceptionMessage(
                            template: 'Not Found: :message at line :line at :file',
                            exception: $exception,
                        )
                        : 'Not Found';
                    $response = new Response(content: $message, status: Response::HTTP_NOT_FOUND);
                }
            }
        } catch (UnauthorizedHttpException) {
            $response = new RedirectResponse(url: './login');
            if (str_contains(haystack: $urlMatcher->getContext()->getBaseUrl(), needle: '/api')) {
                $response = new Response(
                    content: json_encode(value: ['error' => 'Unauthorized access']),
                    status: Response::HTTP_UNAUTHORIZED,
                    headers: ['Content-Type' => 'application/json'],
                );
            }
        } catch (ForbiddenException $exception) {
            $message = Environment::isDebugMode()
                ? $this->formatExceptionMessage(
                    template: 'An error occurred: :message at line :line at :file',
                    exception: $exception,
                )
                : 'Bad Request';
            $response = new Response(content: $message, status: Response::HTTP_FORBIDDEN);
        } catch (BadRequestException $exception) {
            $message = Environment::isDebugMode()
                ? $this->formatExceptionMessage(
                    template: 'An error occurred: :message at line :line at :file',
                    exception: $exception,
                )
                : 'Bad Request';
            $response = new Response(content: $message, status: Response::HTTP_BAD_REQUEST);
        } catch (Throwable $exception) {
            // Log the error for debugging
            error_log(sprintf(
                'Unhandled exception in Application: %s at %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            $message = Environment::isDebugMode()
                ? $this->formatExceptionMessage(
                    template: 'Internal Server Error: :message at line :line at :file',
                    exception: $exception,
                )
                : 'Internal Server Error';

            // Return JSON response for API requests
            if (str_contains(haystack: $urlMatcher->getContext()->getBaseUrl(), needle: '/api')) {
                $content = Environment::isDebugMode()
                    ? json_encode(value: [
                        'error' => 'Internal Server Error',
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                    ])
                    : json_encode(value: ['error' => 'Internal Server Error']);

                $response = new Response(content: $content, status: Response::HTTP_INTERNAL_SERVER_ERROR, headers: [
                    'Content-Type' => 'application/json',
                ]);

                $response->send();
                return;
            }

            $response = new Response(content: $message, status: Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response->send();
    }

    /**
     * Formats an exception message from a template with named placeholders.
     */
    private function formatExceptionMessage(string $template, Throwable $exception): string
    {
        return strtr($template, [
            ':message' => $exception->getMessage(),
            ':line' => (string) $exception->getLine(),
            ':file' => $exception->getFile(),
        ]);
    }
}
