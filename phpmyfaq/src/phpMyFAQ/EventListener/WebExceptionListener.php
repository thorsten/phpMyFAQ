<?php

/**
 * Exception listener for web (non-API) requests
 *
 * Handles exceptions by rendering appropriate error pages or redirecting.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-15
 */

declare(strict_types=1);

namespace phpMyFAQ\EventListener;

use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Controller\Frontend\PageNotFoundController;
use phpMyFAQ\Environment;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Throwable;

class WebExceptionListener
{
    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Skip API requests — handled by ApiExceptionListener
        if (str_starts_with($pathInfo, '/api/') || $request->attributes->get('_api_context', false)) {
            return;
        }

        $throwable = $event->getThrowable();

        $response = match (true) {
            $throwable instanceof ResourceNotFoundException => $this->handleNotFound($event),
            $throwable instanceof UnauthorizedHttpException => new RedirectResponse(url: './login'),
            $throwable instanceof ForbiddenException => $this->handleErrorResponse(
                'An error occurred: :message at line :line at :file',
                'Forbidden',
                Response::HTTP_FORBIDDEN,
                $throwable,
            ),
            $throwable instanceof BadRequestException => $this->handleErrorResponse(
                'An error occurred: :message at line :line at :file',
                'Bad Request',
                Response::HTTP_BAD_REQUEST,
                $throwable,
            ),
            default => $this->handleServerError($throwable),
        };

        $event->setResponse($response);
    }

    private function handleNotFound(ExceptionEvent $event): Response
    {
        $request = $event->getRequest();
        $throwable = $event->getThrowable();

        try {
            $request->attributes->set('_route', 'public.404');
            $request->attributes->set('_controller', PageNotFoundController::class . '::index');
            $controllerResolver = new ControllerResolver();
            $argumentResolver = new ArgumentResolver();
            $controller = $controllerResolver->getController($request);
            $arguments = $argumentResolver->getArguments($request, $controller);
            return call_user_func_array($controller, $arguments);
        } catch (Throwable) {
            return $this->handleErrorResponse(
                'Not Found: :message at line :line at :file',
                'Not Found',
                Response::HTTP_NOT_FOUND,
                $throwable,
            );
        }
    }

    private function handleServerError(Throwable $throwable): Response
    {
        error_log(sprintf(
            'Unhandled exception: %s at %s:%d',
            $throwable->getMessage(),
            $throwable->getFile(),
            $throwable->getLine(),
        ));

        return $this->handleErrorResponse(
            'Internal Server Error: :message at line :line at :file',
            'Internal Server Error',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            $throwable,
        );
    }

    private function handleErrorResponse(
        string $debugTemplate,
        string $fallbackMessage,
        int $statusCode,
        Throwable $throwable,
    ): Response {
        $message = Environment::isDebugMode()
            ? $this->formatExceptionMessage($debugTemplate, $throwable)
            : $fallbackMessage;

        return new Response(content: $message, status: $statusCode);
    }

    private function formatExceptionMessage(string $template, Throwable $throwable): string
    {
        return strtr($template, [
            ':message' => $throwable->getMessage(),
            ':line' => (string) $throwable->getLine(),
            ':file' => $throwable->getFile(),
        ]);
    }
}
