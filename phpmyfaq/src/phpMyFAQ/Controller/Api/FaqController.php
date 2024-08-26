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
use phpMyFAQ\Category;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Entity\FaqEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\MetaData;
use phpMyFAQ\Faq\Statistics;
use phpMyFAQ\Filter;
use phpMyFAQ\Tags;
use phpMyFAQ\User\CurrentUser;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class FaqController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $categoryId = Filter::filterVar($request->get('categoryId'), FILTER_VALIDATE_INT);

        try {
            $result = $faq->getAllAvailableFaqsByCategoryId($categoryId);
            return $this->json($result, Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $faqId = Filter::filterVar($request->get('faqId'), FILTER_VALIDATE_INT);

        $faq->getRecord($faqId);
        $result = $faq->faqRecord;

        if ((is_countable($result) ? count($result) : 0) === 0 || $result['solution_id'] === 42) {
            $result = new stdClass();
            return $this->json($result, Response::HTTP_NOT_FOUND);
        } else {
            return $this->json($result, Response::HTTP_OK);
        }
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $tagId = Filter::filterVar($request->get('tagId'), FILTER_VALIDATE_INT);

        $tags = new Tags($this->configuration);
        $recordIds = $tags->getFaqsByTagId($tagId);

        try {
            $result = $faq->getRecordsByIds($recordIds);
            return $this->json($result, Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faqStatistics = new Statistics($this->configuration);
        $faqStatistics->setUser($currentUser);
        $faqStatistics->setGroups($currentGroups);

        $result = array_values($faqStatistics->getTopTenData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faqStatistics = new Statistics($this->configuration);
        $faqStatistics->setUser($currentUser);
        $faqStatistics->setGroups($currentGroups);

        $result = array_values($faqStatistics->getLatestData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    #[OA\Get(
        path: '/api/v3.0/faqs/trending',
        operationId: 'getTrending',
        description: 'This endpoint returns the trending FAQs for the given language provided by "Accept-Language".',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the FAQ.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'If there\'s at least one trending FAQ.',
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
        description: 'If there\'s not a single trending FAQ.',
        content: new OA\JsonContent(example: []),
    )]
    public function getTrending(): JsonResponse
    {
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faqStatistics = new Statistics($this->configuration);
        $faqStatistics->setUser($currentUser);
        $faqStatistics->setGroups($currentGroups);

        $result = array_values($faqStatistics->getTrendingData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $result = array_values($faq->getStickyRecordsData());

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
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
        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);
        $faq->getAllRecords(
            FAQ_SORTING_TYPE_CATID_FAQID,
            ['lang' => $this->configuration->getLanguage()->getLanguage()]
        );
        $result = $faq->faqRecords;

        if ((is_countable($result) ? count($result) : 0) === 0) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        }

        return $this->json($result, Response::HTTP_OK);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws \JsonException
     */
    #[OA\Post(
        path: '/api/v3.0/faq/create',
        operationId: 'createFaq',
        tags: ['Endpoints with Authentication'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        description: 'The category ID is a required value, the category name is optional. If the category name is ' .
            'present and the ID can be mapped, the category ID from the name will be used. If the category name ' .
            'cannot be mapped, a 409 error is thrown.',
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(
                required: [
                    'language',
                    'category-id',
                    'category-name',
                    'question',
                    'answer',
                    'keywords',
                    'author',
                    'email',
                    'is-active',
                    'is-sticky'
                ],
                properties: [
                    new OA\Property(property: 'language', type: 'string'),
                    new OA\Property(property: 'category-id', type: 'integer'),
                    new OA\Property(property: 'category-name', type: 'string'),
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                    new OA\Property(property: 'keywords', type: 'string'),
                    new OA\Property(property: 'author', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'is-active', type: 'boolean'),
                    new OA\Property(property: 'is-sticky', type: 'boolean')
                ],
                type: 'object'
            ),
            example: '{
                "language": "de",
                "category-id": 1,
                "category-name": "Queen Songs",
                "question": "Is this the world we created?",
                "answer": "What did we do it for, is this the world we invaded, against the law, so it seems in the ' .
                    'end, is this what we\'re all living for today",
                "keywords": "phpMyFAQ, FAQ, Foo, Bar",
                "author": "Freddie Mercury",
                "email": "freddie.mercury@example.org",
                "is-active": "true",
                "is-sticky": "false"
            }'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'If all posted data is correct.',
        content: new OA\JsonContent(example: '{ "stored": true }')
    )]
    #[OA\Response(
        response: 400,
        description: 'If something didn\'t worked out.',
        content: new OA\JsonContent(
            example: '{ "stored": false, "error": "It is not allowed, that the question title contains a hash." }'
        )
    )]
    #[OA\Response(
        response: 409,
        description: 'If the parent category name cannot be mapped.',
        content: new OA\JsonContent(
            example: '{ "stored": false, "error": "The given category name was not found" }'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.'
    )]
    public function create(Request $request): JsonResponse
    {
        $this->hasValidToken();

        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $currentLanguage = $this->configuration->getLanguage()->getLanguage();

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $languageCode = Filter::filterVar($data->language, FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterVar($data->{'category-id'}, FILTER_VALIDATE_INT);
        if (isset($data->{'category-name'})) {
            $categoryName = Filter::filterVar($data->{'category-name'}, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $categoryName = null;
        }

        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($data->{'is-active'}, FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($data->{'is-sticky'}, FILTER_VALIDATE_BOOLEAN);

        // Check if category name can be mapped
        if (!is_null($categoryName)) {
            $categoryIdFound = $category->getCategoryIdFromName($categoryName);
            if ($categoryIdFound === false) {
                $result = [
                    'stored' => false,
                    'error' => 'The given category name was not found.'
                ];
                return $this->json($result, Response::HTTP_CONFLICT);
            }

            $categoryId = $categoryIdFound;
        }

        if ($faq->hasTitleAHash($question)) {
            $result = [
                'stored' => false,
                'error' => 'It is not allowed, that the question title contains a hash.'
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $categories = [ $categoryId ];
        $isActive = !is_null($isActive);
        $isSticky = !is_null($isSticky);

        $faqData = new FaqEntity();
        $faqData
            ->setLanguage($languageCode)
            ->setQuestion($question)
            ->setAnswer($answer)
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($isActive)
            ->setSticky($isSticky)
            ->setComment(false)
            ->setNotes('');

        $faqId = $faq->create($faqData);

        $faqMetaData = new MetaData($this->configuration);
        $faqMetaData
            ->setFaqId($faqId)
            ->setFaqLanguage($languageCode)
            ->setCategories($categories)
            ->save();

        return $this->json(['stored' => true], Response::HTTP_CREATED);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     * @throws \JsonException
     */
    #[OA\Put(
        path: '/api/v3.0/faq/update',
        operationId: 'updateFaq',
        description: 'Used to update a FAQ in one existing category.',
        tags: ['Endpoints with Authentication']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the login.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Header(
        header: 'x-pmf-token',
        description: 'phpMyFAQ client API Token, generated in admin backend',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(
                required: [
                    'faq-id',
                    'language',
                    'category-id',
                    'question',
                    'answer',
                    'keywords',
                    'author',
                    'email',
                    'is-active',
                    'is-sticky'
                ],
                properties: [
                    new OA\Property(property: 'faq-id', type: 'integer'),
                    new OA\Property(property: 'language', type: 'string'),
                    new OA\Property(property: 'category-id', type: 'integer'),
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'answer', type: 'string'),
                    new OA\Property(property: 'keywords', type: 'string'),
                    new OA\Property(property: 'author', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                    new OA\Property(property: 'is-active', type: 'boolean'),
                    new OA\Property(property: 'is-sticky', type: 'boolean')
                ],
                type: 'object'
            ),
            example: '{
                "faq-id": 1,
                "language": "de",
                "category-id": 1,
                "question": "Is this the world we updated?",
                "answer": "What did we do it for, is this the world we invaded, against the law, so it seems in the ' .
                    'end, is this what we\'re all living for today",
                "keywords": "phpMyFAQ, FAQ, Foo, Bar",
                "author": "Freddie Mercury",
                "email": "freddie.mercury@example.org",
                "is-active": "true",
                "is-sticky": "false"
            }'
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'If all posted data is correct.',
        content: new OA\JsonContent(example: '{ "stored": true }')
    )]
    #[OA\Response(
        response: 400,
        description: 'If something didn\'t worked out.',
        content: new OA\JsonContent(
            example: '{ "stored": false, "error": "It is not allowed, that the question title contains a hash." }'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.'
    )]
    public function update(Request $request): JsonResponse
    {
        $this->hasValidToken();

        $user = CurrentUser::getCurrentUser($this->configuration);

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $currentLanguage = $this->configuration->getLanguage()->getLanguage();

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $faq = new Faq($this->configuration);
        $faq->setUser($currentUser);
        $faq->setGroups($currentGroups);

        $faqId = Filter::filterVar($data->{'faq-id'}, FILTER_VALIDATE_INT);
        $languageCode = Filter::filterVar($data->language, FILTER_SANITIZE_SPECIAL_CHARS);
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $answer = Filter::filterVar($data->answer, FILTER_SANITIZE_SPECIAL_CHARS);
        $keywords = Filter::filterVar($data->keywords, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_SANITIZE_EMAIL);
        $isActive = Filter::filterVar($data->{'is-active'}, FILTER_VALIDATE_BOOLEAN);
        $isSticky = Filter::filterVar($data->{'is-sticky'}, FILTER_VALIDATE_BOOLEAN);

        if ($faq->hasTitleAHash($question)) {
            $result = [
                'stored' => false,
                'error' => 'It is not allowed, that the question title contains a hash.'
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }

        $isActive = !is_null($isActive);
        $isSticky = !is_null($isSticky);

        $faqData = new FaqEntity();
        $faqData
            ->setId($faqId)
            ->setRevisionId(0)
            ->setLanguage($languageCode)
            ->setQuestion($question)
            ->setAnswer($answer)
            ->setKeywords($keywords)
            ->setAuthor($author)
            ->setEmail($email)
            ->setActive($isActive)
            ->setSticky($isSticky)
            ->setComment(false)
            ->setNotes('');

        $faq->update($faqData);

        return $this->json(['stored' => true], Response::HTTP_OK);
    }
}
