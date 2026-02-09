<?php

/**
 * OAuth2 endpoints controller.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Auth\OAuth2\AuthorizationServer as OAuth2AuthorizationServer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class OAuth2Controller extends AbstractApiController
{
    private ?OAuth2AuthorizationServer $authorizationServer = null;

    public function setAuthorizationServer(OAuth2AuthorizationServer $authorizationServer): void
    {
        $this->authorizationServer = $authorizationServer;
    }

    /**
     * OAuth2 token endpoint.
     */
    #[Route(path: 'oauth/token', name: 'api.oauth2.token', methods: ['POST'])]
    public function token(Request $request): JsonResponse
    {
        $authorizationServer = $this->getAuthorizationServer();

        try {
            $result = $authorizationServer->issueToken($request);
            return $this->json($result['body'], $result['status'], $result['headers'] ?? []);
        } catch (\RuntimeException $exception) {
            $statusCode = $exception->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }

            if ($statusCode >= 500) {
                $this->configuration->getLogger()->error('OAuth2 token error: ' . $exception->getMessage());

                return $this->json([
                    'error' => 'oauth2_unavailable',
                    'error_description' => 'Internal server error',
                ], $statusCode);
            }

            return $this->json([
                'error' => 'oauth2_unavailable',
                'error_description' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * OAuth2 authorization endpoint for authorization_code grant.
     *
     * @throws \Exception
     */
    #[Route(path: 'oauth/authorize', name: 'api.oauth2.authorize', methods: ['GET', 'POST'])]
    public function authorize(Request $request): Response
    {
        if (!$this->currentUser?->isLoggedIn()) {
            return $this->json(['error' => 'access_denied', 'error_description' => 'User is not authenticated.'], 401);
        }

        if ($request->getMethod() === 'GET') {
            return $this->json([
                'error' => 'consent_required',
                'error_description' => 'Submit a POST request with an explicit approve parameter and CSRF token.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $csrf = $request->headers->get('X-CSRF-Token') ?? $request->request->get('csrf', '');
        if (!$this->verifySessionCsrfToken('oauth2-authorize', (string) $csrf)) {
            return $this->json([
                'error' => 'invalid_request',
                'error_description' => 'CSRF token validation failed.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (!$request->request->has('approve')) {
            return $this->json([
                'error' => 'invalid_request',
                'error_description' => 'Missing required approve parameter.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $isApproved = filter_var($request->request->get('approve'), FILTER_VALIDATE_BOOLEAN);

        try {
            $result = $this->getAuthorizationServer()->completeAuthorization(
                $request,
                (string) $this->currentUser->getUserId(),
                $isApproved,
            );
        } catch (\RuntimeException $exception) {
            $statusCode = $exception->getCode();
            if ($statusCode < 400 || $statusCode > 599) {
                $statusCode = Response::HTTP_SERVICE_UNAVAILABLE;
            }

            if ($statusCode >= 500) {
                $this->configuration->getLogger()->error('OAuth2 authorization error: ' . $exception->getMessage());

                return $this->json([
                    'error' => 'oauth2_unavailable',
                    'error_description' => 'Internal server error',
                ], $statusCode);
            }

            return $this->json([
                'error' => 'oauth2_unavailable',
                'error_description' => $exception->getMessage(),
            ], $statusCode);
        }

        if (isset($result['headers']['Location'])) {
            return new RedirectResponse($result['headers']['Location'], $result['status'], $result['headers']);
        }

        $contentType = $result['headers']['Content-Type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $data = json_decode((string) $result['body'], true);
            if (is_array($data)) {
                return $this->json($data, $result['status'], $result['headers']);
            }
        }

        return new Response((string) $result['body'], $result['status'], $result['headers']);
    }

    private function getAuthorizationServer(): OAuth2AuthorizationServer
    {
        if ($this->authorizationServer instanceof OAuth2AuthorizationServer) {
            return $this->authorizationServer;
        }

        $serviceId = 'phpmyfaq.auth.oauth2.authorization-server';
        if (!$this->container->has($serviceId)) {
            throw new \RuntimeException('OAuth2 authorization server service not registered: ' . $serviceId);
        }

        $server = $this->container->get($serviceId);
        if (!$server instanceof OAuth2AuthorizationServer) {
            throw new \RuntimeException('OAuth2 authorization server service returned an unexpected type: '
            . $serviceId);
        }

        return $server;
    }
}
