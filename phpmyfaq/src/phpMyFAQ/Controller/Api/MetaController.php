<?php

/**
 * The Metadata Controller for the REST API
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
 * @since     2026-04-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Api\MetaService;
use phpMyFAQ\Api\OAuthDiscoveryService;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class MetaController extends AbstractController
{
    private MetaService $metaService;

    public function __construct(?MetaService $metaService = null)
    {
        parent::__construct();
        $this->metaService = $metaService ?? new MetaService(
            $this->configuration,
            new OAuthDiscoveryService($this->configuration),
        );

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    #[OA\Get(path: '/api/v4.0/meta', operationId: 'getMeta', tags: ['Public Endpoints'])]
    #[OA\Response(
        response: 200,
        description: 'Returns bootstrap metadata for the phpMyFAQ instance.',
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'version', type: 'string', example: '4.0.0'),
            new OA\Property(property: 'title', type: 'string', example: 'phpMyFAQ Codename Porus'),
            new OA\Property(property: 'language', type: 'string', example: 'en'),
            new OA\Property(property: 'availableLanguages', type: 'object', example: [
                'de' => 'German',
                'en' => 'English',
            ]),
            new OA\Property(property: 'enabledFeatures', type: 'object'),
            new OA\Property(
                property: 'publicLogoUrl',
                type: 'string',
                example: 'https://localhost/assets/images/logo-transparent.svg',
            ),
            new OA\Property(property: 'themeColors', type: 'object', example: [
                'light' => [
                    '--bs-primary' => '#083c83',
                    '--bs-body-bg' => '#ffffff',
                ],
                'dark' => [
                    '--bs-primary' => '#083c83',
                    '--bs-body-bg' => 'var(--bs-dark)',
                ],
                'highContrast' => [
                    '--bs-primary' => '#ffff00',
                    '--bs-body-bg' => '#000000',
                ],
            ]),
            new OA\Property(property: 'oauthDiscovery', type: 'object'),
        ]),
    )]
    #[Route(path: 'v4.0/meta', name: 'api.meta', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json($this->metaService->getPublicMetadata());
    }
}
