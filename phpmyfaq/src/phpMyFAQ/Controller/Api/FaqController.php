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
use stdClass;
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
        path: '/api/v3.0/faq/{categoryId}/{faqId}',
        operationId: 'getFaqById',
        description: 'This endpoint returns the FAQ for the given FAQ ID and the language provided by ' .
            '"Accept-Language".',
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
    #[OA\Parameter(
        name: 'faqId',
        description: 'The FAQ ID.',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'If the FAQ exists.',
        content: new OA\JsonContent(example: '{
            "id": 1,
            "lang": "en",
            "solution_id": 1000,
            "revision_id": 0,
            "active": "yes",
            "sticky": 0,
            "keywords": "",
            "title": "Is there life after death?",
            "content": "Maybe!",
            "author": "phpMyFAQ User",
            "email": "user@example.org",
            "comment": "y",
            "date": "2019-10-10 17:54",
            "dateStart": "00000000000000",
            "dateEnd": "99991231235959",
            "created": "2019-09-03T21:30:17+02:00"
        }')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there are no FAQs for the given FAQ ID.',
        content: new OA\JsonContent(example: []),
    )]
    public function getById(Request $request): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faq->getRecord($faqId);
        $result = $faq->faqRecord;

        if ((is_countable($result) ? count($result) : 0) === 0 || $result['solution_id'] === 42) {
            $result = new stdClass();
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
        }

        $jsonResponse->setData($result);
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

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/popular',
        operationId: 'getPopular',
        description: 'This endpoint returns the popular FAQs for the given language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'If there\'s at least one popular FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "date": "2019-07-13T11:28:00+0200",
                "question": "How can I survive without phpMyFAQ?",
                "answer": "A good question!",
                "visits": 10,
                "url": "https://www.example.org/index.php?action=faq&cat=1&id=36&artlang=de"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there\'s not a single popular FAQ.',
        content: new OA\JsonContent(example: []),
    )]
    public function getPopular(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $result = array_values($faq->getTopTenData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($result);
        return $jsonResponse;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/latest',
        operationId: 'getLatest',
        description: 'This endpoint returns the latest FAQs for the given language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'If there\'s at least one latest FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "date": "2019-07-13T11:28:00+0200",
                "question": "How can I survive without phpMyFAQ?",
                "answer": "A good question!",
                "visits": 10,
                "url": "https://www.example.org/index.php?action=faq&cat=1&id=36&artlang=de"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there\'s not one latest FAQ.',
        content: new OA\JsonContent(example: []),
    )]
    public function getLatest(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $result = array_values($faq->getLatestData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($result);
        return $jsonResponse;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/sticky',
        operationId: 'getSticky',
        description: 'This endpoint returns the sticky FAQs for the given language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'If there\'s at least one sticky FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "question": "How can I survive without phpMyFAQ?",
                "url": "https://www.example.org/index.php?action=faq&cat=1&id=36&artlang=de",
                "id": "8",
                "order": "1"
            },
            {
                "question": "Is there life after death?",
                "url": "https://www.example.org/index.php?action=faq&cat=1&id=1&artlang=en",
                "id": "10",
                "order": "2"
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there\'s not one sticky FAQ.',
        content: new OA\JsonContent(example: []),
    )]
    public function getSticky(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $result = array_values($faq->getStickyRecordsData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($result);
        return $jsonResponse;
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs',
        operationId: 'getAll',
        description: 'This endpoint returns all the FAQs for the given language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'If there\'s at least one FAQ.',
        content: new OA\JsonContent(example: '[
            {
                "id": "1",
                "lang": "en",
                "solution_id": "1000",
                "revision_id": "0",
                "active": "yes",
                "sticky": "0",
                "keywords": "",
                "title": "Is there life after death?",
                "content": "Maybe!",
                "author": "phpMyFAQ User",
                "email": "user@example.org",
                "comment": "y",
                "updated": "2009-10-10 17:54:00",
                "dateStart": "00000000000000",
                "dateEnd": "99991231235959",
                "created": "2008-09-03T21:30:17+02:00",
                "notes": ""
            }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If there\'s not one single FAQ.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(): JsonResponse
    {
        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(FAQ_SORTING_TYPE_CATID_FAQID, ['lang' => $configuration->getLanguage()->getLanguage()]);
        $result = $faq->faqRecords;

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_OK);
        $jsonResponse->setData($result);
        return $jsonResponse;
    }
}
