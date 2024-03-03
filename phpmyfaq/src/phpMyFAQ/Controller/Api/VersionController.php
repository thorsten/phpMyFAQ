<?php

/**
 * The Version Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Controller\Api;

use phpMyFAQ\Configuration;
use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

class VersionController extends AbstractController
{
    #[OA\Get(path: '/api/v3.0/version', operationId: 'getVersion', tags: ['Public Endpoints'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the phpMyFAQ version number as string.',
        content: new OA\JsonContent(example: '4.0.0'),
    )]
    public function index(): JsonResponse
    {
        return $this->json(Configuration::getConfigurationInstance()->getVersion());
    }
}
