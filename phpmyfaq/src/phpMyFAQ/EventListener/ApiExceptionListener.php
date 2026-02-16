<?php

/**
 * Exception listener for API requests
 *
 * Converts exceptions to RFC 7807 ProblemDetails JSON responses for API endpoints.
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

use phpMyFAQ\Api\ProblemDetails;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Environment;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

readonly class ApiExceptionListener
{
    public function __construct(
        private ?Configuration $configuration = null,
    ) {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // Only handle API requests
        if (!str_starts_with($pathInfo, '/api/') && !$request->attributes->get('_api_context', false)) {
            return;
        }

        $throwable = $event->getThrowable();

        [$status, $defaultDetail] = match (true) {
            $throwable instanceof ResourceNotFoundException => [
                Response::HTTP_NOT_FOUND,
                'The requested resource was not found.',
            ],
            $throwable instanceof UnauthorizedHttpException => [
                Response::HTTP_UNAUTHORIZED,
                'Unauthorized access.',
            ],
            $throwable instanceof ForbiddenException => [
                Response::HTTP_FORBIDDEN,
                'Access to this resource is forbidden.',
            ],
            $throwable instanceof BadRequestException => [
                Response::HTTP_BAD_REQUEST,
                'The request could not be understood or was missing required parameters.',
            ],
            default => [
                Response::HTTP_INTERNAL_SERVER_ERROR,
                'An unexpected error occurred while processing your request.',
            ],
        };

        if ($status === Response::HTTP_INTERNAL_SERVER_ERROR) {
            error_log(sprintf(
                'Unhandled exception in API: %s at %s:%d',
                $throwable->getMessage(),
                $throwable->getFile(),
                $throwable->getLine(),
            ));
        }

        $response = $this->createProblemDetailsResponse($request, $status, $throwable, $defaultDetail);
        $event->setResponse($response);
    }

    private function createProblemDetailsResponse(
        \Symfony\Component\HttpFoundation\Request $request,
        int $status,
        \Throwable $throwable,
        string $defaultDetail,
    ): Response {
        $baseUrl = '';
        if ($this->configuration !== null) {
            $baseUrl = rtrim($this->configuration->getDefaultUrl(), '/');
        }

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
