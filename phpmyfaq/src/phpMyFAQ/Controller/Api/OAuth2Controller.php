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

            return $this->json([
                'error' => 'oauth2_unavailable',
                'error_description' => $exception->getMessage(),
            ], $statusCode);
        }
    }

    /**
     * OAuth2 authorization endpoint for authorization_code grant.
     */
    #[Route(path: 'oauth/authorize', name: 'api.oauth2.authorize', methods: ['GET', 'POST'])]
    public function authorize(Request $request): Response
    {
        if (!$this->currentUser->isLoggedIn()) {
            return $this->json(['error' => 'access_denied', 'error_description' => 'User is not authenticated.'], 401);
        }

        $approval = $request->get('approve', 'true');
        $isApproved = filter_var($approval, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($isApproved === null) {
            $isApproved = true;
        }

        try {
            $result = $this->getAuthorizationServer()->completeAuthorization(
                $request,
                (string) $this->currentUser->getUserId(),
                $isApproved,
            );
        } catch (\RuntimeException $exception) {
            return $this->json([
                'error' => 'oauth2_unavailable',
                'error_description' => $exception->getMessage(),
            ], 503);
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
        return $this->authorizationServer ?? $this->container->get(id: 'phpmyfaq.auth.oauth2.authorization-server');
    }
}
