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

use phpMyFAQ\Api\ProblemDetails;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Controller\Frontend\PageNotFoundController;
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
        $request ??= Request::createFromGlobals();
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

            // Set container in configuration for lazy loading of services like translation provider
            $configuration->setContainer($this->container);

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
            // For API requests, return RFC 7807 JSON response
            if ($this->isApiContext) {
                $response = $this->createProblemDetailsResponse(
                    request: $request,
                    status: Response::HTTP_NOT_FOUND,
                    throwable: $exception,
                    defaultDetail: 'The requested resource was not found.',
                );
            } else {
                // For web requests, forward to the PageNotFoundController
                try {
                    $request->attributes->set('_route', 'public.404');
                    $request->attributes->set('_controller', PageNotFoundController::class . '::index');
                    $controller = $this->controllerResolver->getController($request);
                    $arguments = $argumentResolver->getArguments($request, $controller);
                    $response = call_user_func_array($controller, $arguments);
                } catch (Throwable) {
                    // Fallback if the controller fails
                    $message = Environment::isDebugMode()
                        ? $this->formatExceptionMessage(
                            template: 'Not Found: :message at line :line at :file',
                            throwable: $exception,
                        )
                        : 'Not Found';
                    $response = new Response(content: $message, status: Response::HTTP_NOT_FOUND);
                }
            }
        } catch (UnauthorizedHttpException $exception) {
            if ($this->isApiContext) {
                $response = $this->createProblemDetailsResponse(
                    request: $request,
                    status: Response::HTTP_UNAUTHORIZED,
                    throwable: $exception,
                    defaultDetail: 'Unauthorized access.',
                );
            } else {
                $response = new RedirectResponse(url: './login');
            }
        } catch (ForbiddenException $exception) {
            if ($this->isApiContext) {
                $response = $this->createProblemDetailsResponse(
                    request: $request,
                    status: Response::HTTP_FORBIDDEN,
                    throwable: $exception,
                    defaultDetail: 'Access to this resource is forbidden.',
                );
            } else {
                $message = Environment::isDebugMode()
                    ? $this->formatExceptionMessage(
                        template: 'An error occurred: :message at line :line at :file',
                        throwable: $exception,
                    )
                    : 'Forbidden';
                $response = new Response(content: $message, status: Response::HTTP_FORBIDDEN);
            }
        } catch (BadRequestException $exception) {
            if ($this->isApiContext) {
                $response = $this->createProblemDetailsResponse(
                    request: $request,
                    status: Response::HTTP_BAD_REQUEST,
                    throwable: $exception,
                    defaultDetail: 'The request could not be understood or was missing required parameters.',
                );
            } else {
                $message = Environment::isDebugMode()
                    ? $this->formatExceptionMessage(
                        template: 'An error occurred: :message at line :line at :file',
                        throwable: $exception,
                    )
                    : 'Bad Request';
                $response = new Response(content: $message, status: Response::HTTP_BAD_REQUEST);
            }
        } catch (Throwable $exception) {
            // Log the error for debugging
            error_log(sprintf(
                'Unhandled exception in Application: %s at %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));

            if ($this->isApiContext) {
                $response = $this->createProblemDetailsResponse(
                    request: $request,
                    status: Response::HTTP_INTERNAL_SERVER_ERROR,
                    throwable: $exception,
                    defaultDetail: 'An unexpected error occurred while processing your request.',
                );
            } else {
                $message = Environment::isDebugMode()
                    ? $this->formatExceptionMessage(
                        template: 'Internal Server Error: :message at line :line at :file',
                        throwable: $exception,
                    )
                    : 'Internal Server Error';
                $response = new Response(content: $message, status: Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }

        $response->send();
    }

    /**
     * Formats an exception message from a template with named placeholders.
     */
    private function formatExceptionMessage(string $template, Throwable $throwable): string
    {
        return strtr($template, [
            ':message' => $throwable->getMessage(),
            ':line' => (string) $throwable->getLine(),
            ':file' => $throwable->getFile(),
        ]);
    }

    /**
     * Creates a ProblemDetails response for API errors.
     */
    private function createProblemDetailsResponse(
        Request $request,
        int $status,
        Throwable $throwable,
        string $defaultDetail,
    ): Response {
        $configuration = $this->container->get(id: 'phpmyfaq.configuration');
        $baseUrl = rtrim($configuration->getDefaultUrl(), '/');

        $type = match ($status) {
            Response::HTTP_BAD_REQUEST => $baseUrl . '/problems/bad-request',
            Response::HTTP_UNAUTHORIZED => $baseUrl . '/problems/unauthorized',
            Response::HTTP_FORBIDDEN => $baseUrl . '/problems/forbidden',
            Response::HTTP_NOT_FOUND => $baseUrl . '/problems/not-found',
            Response::HTTP_CONFLICT => $baseUrl . '/problems/conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => $baseUrl . '/problems/validation-error',
            Response::HTTP_TOO_MANY_REQUESTS => $baseUrl . '/problems/rate-limited',
            Response::HTTP_INTERNAL_SERVER_ERROR => $baseUrl . '/problems/internal-server-error',
            default => $baseUrl . '/problems/http-error',
        };

        $title = match ($status) {
            Response::HTTP_BAD_REQUEST => 'Bad Request',
            Response::HTTP_UNAUTHORIZED => 'Unauthorized',
            Response::HTTP_FORBIDDEN => 'Forbidden',
            Response::HTTP_NOT_FOUND => 'Resource not found',
            Response::HTTP_CONFLICT => 'Conflict',
            Response::HTTP_UNPROCESSABLE_ENTITY => 'Validation failed',
            Response::HTTP_TOO_MANY_REQUESTS => 'Too many requests',
            Response::HTTP_INTERNAL_SERVER_ERROR => 'Internal Server Error',
            default => 'HTTP error',
        };

        $detail = Environment::isDebugMode()
            ? $throwable->getMessage() . ' at line ' . $throwable->getLine() . ' in ' . $throwable->getFile()
            : $defaultDetail;

        $problemDetails = new ProblemDetails(
            type: $type,
            title: $title,
            status: $status,
            detail: $detail,
            instance: $request->getPathInfo(),
        );

        $response = new Response(
            content: json_encode($problemDetails->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            status: $status,
        );
        $response->headers->set('Content-Type', 'application/problem+json');

        return $response;
    }
}
