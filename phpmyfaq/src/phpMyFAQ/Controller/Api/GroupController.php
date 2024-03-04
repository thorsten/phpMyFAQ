<?php

/**
 * The Group Controller for the REST API
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

use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class GroupController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[OA\Get(
        path: '/api/v3.0/groups',
        operationId: 'getGroups',
        description: 'Used to fetch all group IDs.',
        tags: ['Endpoints with Authentication']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a list of group IDs.',
        content: new OA\JsonContent(example: '
        [
            {
                "group-id": 1
            },
            {
                "group-id": 2
            }
        ]')
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.'
    )]
    public function list(): JsonResponse
    {
        $this->userIsAuthenticated();

        $faqConfig = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($faqConfig);

        $mediumPermission = new MediumPermission($faqConfig);
        $result = $mediumPermission->getAllGroups($user);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
