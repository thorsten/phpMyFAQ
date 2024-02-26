<?php

/**
 * The Faq Controller for the REST API
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-02-26
 */

namespace phpMyFAQ\Controller\Api;

use Exception;
use OpenApi\Attributes as OA;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FaqController extends AbstractController
{
    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/{categoryId}',
        operationId: 'getByCategoryId',
        description: 'This endpoint returns all the FAQs with a preview of the answer for the given category ID and ' .
            'the language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'categoryId',
        description: 'The category ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'If the category returns at least one FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "record_id": 1,
                "record_lang": "en",
                "category_id": 1,
                "record_title": "Is there life after death?",
                "record_preview": "Maybe!",
                "record_link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=1&artlang=en",
                "record_updated": "20191010175452",
                "visits": 3,
                "record_created": "2018-09-03T21:30:17+02:00"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If the category has no FAQs.',
        content: new OA\JsonContent(example: []),
    )]
    public function getByCategoryId(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);

        try {
            $result = $faq->getAllAvailableFaqsByCategoryId($categoryId);
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData($result);
        } catch (Exception $e) {
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $jsonResponse->setData(['error' => $e->getMessage()]);
            return $jsonResponse;
        }

        return $jsonResponse;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/tags/{tagId}',
        operationId: 'getByTagId',
        description: 'This endpoint returns all the FAQs for the given tag ID and the language provided by ' . '
        "Accept-Language"',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'tagId',
        description: 'The tag ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'If the tag ID returns at least one FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "record_id": 1,
                "record_lang": "en",
                "category_id": 1,
                "record_title": "Is there life after death?",
                "record_preview": "Maybe!",
                "record_link": "/phpmyfaq/phpmyfaq/index.php?action=faq&cat=1&id=1&artlang=en",
                "record_updated": "20191010175452",
                "visits": 3,
                "record_created": "2018-09-03T21:30:17+02:00"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If the tag ID has no FAQs.',
        content: new OA\JsonContent(example: []),
    )]
    public function getByTagId(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $tagId = Filter::filterVar($request->get('tagId'), FILTER_VALIDATE_INT);

        $tags = new Tags($configuration);
        $recordIds = $tags->getFaqsByTagId($tagId);

        try {
            $result = $faq->getRecordsByIds($recordIds);
            $jsonResponse->setStatusCode(Response::HTTP_OK);
            $jsonResponse->setData($result);
        } catch (Exception $e) {
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $jsonResponse->setData(['error' => $e->getMessage()]);
            return $jsonResponse;
        }

        return $jsonResponse;
    }

    public function getPopular(): JsonResponse
    {
        // ...
    }

    public function getLatest(): JsonResponse
    {
        // ...
    }

    public function getSticky(): JsonResponse
    {
        // ...
    }

    public function list(): JsonResponse
    {
        // ...
    }
}
