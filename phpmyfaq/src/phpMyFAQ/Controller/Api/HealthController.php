<?php

/**
 * Health endpoint for container orchestrators and uptime monitors.
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
 * @since     2026-09-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * A 200 response means the application bootstrapped, which includes a working
 * database connection: a failed connection is turned into a 500 problem
 * response by the API front controller before any route runs. Before the
 * installation the bootstrap redirects to the installer instead.
 *
 * The endpoint is deliberately independent of the "api.enableAccess" setting
 * so that Docker HEALTHCHECKs keep working when the public API is disabled.
 */
final class HealthController extends AbstractController
{
    #[OA\Get(path: '/api/health', operationId: 'getHealth', tags: ['Public Endpoints'])]
    #[OA\Response(
        response: 200,
        description: 'The application is running and connected to its database.',
        content: new OA\JsonContent(example: '{"status": "ok"}'),
    )]
    #[Route(path: 'health', name: 'api.health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $response = $this->json(['status' => 'ok']);
        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }
}
