<?php

/**
 * OAuth discovery endpoint controller.
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
 * @since     2026-04-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use OpenApi\Attributes as OA;
use phpMyFAQ\Api\OAuthDiscoveryService;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class OAuthDiscoveryController extends AbstractController
{
    public function __construct(
        private readonly OAuthDiscoveryService $oAuthDiscoveryService,
    ) {
        parent::__construct();
    }

    #[OA\Get(
        path: '/.well-known/oauth-authorization-server',
        operationId: 'getOAuthAuthorizationServerMetadata',
        tags: ['Public Endpoints'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns OAuth 2.0 Authorization Server Metadata.',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'issuer', type: 'string', example: 'https://localhost/api'),
            new OA\Property(property: 'authorization_endpoint', type: 'string'),
            new OA\Property(property: 'token_endpoint', type: 'string'),
            new OA\Property(property: 'grant_types_supported', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(property: 'response_types_supported', type: 'array', items: new OA\Items(type: 'string')),
            new OA\Property(
                property: 'token_endpoint_auth_methods_supported',
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ]),
    )]
    #[Route(path: '/.well-known/oauth-authorization-server', name: 'public.oauth.discovery', methods: ['GET'])]
    public function index(): JsonResponse
    {
        if (!$this->oAuthDiscoveryService->isEnabled()) {
            throw new NotFoundHttpException('OAuth 2.0 authorization server metadata is not available.');
        }

        return $this->json($this->oAuthDiscoveryService->getDiscoveryDocument());
    }
}
