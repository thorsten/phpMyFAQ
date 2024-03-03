<?php

/**
 * The Question Controller for the REST API
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
 * @since     2024-02-27
 */

namespace phpMyFAQ\Controller\Api;

use OpenApi\Attributes as OA;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\QuestionHelper;
use phpMyFAQ\Question;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class QuestionController extends AbstractController
{
    /**
     * @throws Exception
     * @throws \JsonException
     */
    #[OA\Post(
        path: '/api/v3.0/question',
        operationId: 'createQuestion',
        tags: ['Endpoints with Authentication'],
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the question.',
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
                    'language',
                    'category-id',
                    'question',
                    'author',
                    'email'
                ],
                properties: [
                    new OA\Property(property: 'language', type: 'string'),
                    new OA\Property(property: 'category-id', type: 'integer'),
                    new OA\Property(property: 'question', type: 'string'),
                    new OA\Property(property: 'author', type: 'string'),
                    new OA\Property(property: 'email', type: 'string'),
                ],
                type: 'object'
            ),
            example: '{
                "language": "de",
                "category-id": "1",
                "question": "Is this the world we created?",
                "author": "Freddie Mercury",
                "email": "freddie.mercury@example.org"
            }'
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Used to add a new question in one existing category.',
        content: new OA\JsonContent(example: '{ "stored": true }')
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.'
    )]
    public function create(Request $request): JsonResponse
    {
        $this->hasValidToken();

        $jsonResponse = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);
        $languageCode = Filter::filterVar($data->language, FILTER_SANITIZE_SPECIAL_CHARS);
        $categoryId = Filter::filterVar($data->{'category-id'}, FILTER_VALIDATE_INT);
        $question = Filter::filterVar($data->question, FILTER_SANITIZE_SPECIAL_CHARS);
        $author = Filter::filterVar($data->author, FILTER_SANITIZE_SPECIAL_CHARS);
        $email = Filter::filterVar($data->email, FILTER_SANITIZE_SPECIAL_CHARS);

        $visibility = $configuration->get('records.enableVisibilityQuestions') ? 'Y' : 'N';

        $questionData = [
            'username' => $author,
            'email' => $email,
            'category_id' => $categoryId,
            'question' => $question,
            'is_visible' => $visibility
        ];

        $questionObject = new Question($configuration);
        $questionObject->addQuestion($questionData);

        $category = new Category($configuration);
        $category->getCategoryData($categoryId);
        $categories = $category->getAllCategories();

        $questionHelper = new QuestionHelper($configuration, $category);
        try {
            $questionHelper->sendSuccessMail($questionData, $categories);
        } catch (TransportExceptionInterface | Exception $e) {
            $jsonResponse->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $jsonResponse->setData(['error' => $e->getMessage() ]);
            return $jsonResponse;
        }

        $jsonResponse->setStatusCode(Response::HTTP_CREATED);
        $jsonResponse->setData(['stored' => true]);
        return $jsonResponse;
    }
}
