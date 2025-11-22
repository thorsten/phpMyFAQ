<?php

/**
 * The Tags Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

final class TagController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException(challenge: 'API is not enabled');
        }
    }

    /**
     * @throws \Exception
     */
    #[OA\Get(path: '/api/v3.1/tags', operationId: 'getTags', tags: ['Public Endpoints'])]
    #[OA\Response(
        response: 200,
        description: 'Returns the tags for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '[
            {"tagId": 4, "tagName": "phpMyFAQ", "tagFrequency": 3 },
            {"tagId": 1, "tagName": "PHP 8", "tagFrequency": 2 }
        ]'),
    )]
    #[OA\Response(response: 404, description: 'If no tags are stored.', content: new OA\JsonContent(example: []))]
    public function list(): JsonResponse
    {
        $tags = $this->container->get(id: 'phpmyfaq.tags');
        [$currentUser, $currentGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);
        $tags->setUser($currentUser);
        $tags->setGroups($currentGroups);
        $result = $tags->getPopularTagsAsArray(limit: 16);
        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json([], Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }
}
