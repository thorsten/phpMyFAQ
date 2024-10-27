<?php

/**
 * The Category Controller for the REST API
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
use phpMyFAQ\Category;
use phpMyFAQ\Category\Order;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Entity\CategoryEntity;
use phpMyFAQ\Filter;
use phpMyFAQ\Language;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class CategoryController extends AbstractController
{
    public function __construct()
    {
        parent::__construct();

        if (!$this->isApiEnabled()) {
            throw new UnauthorizedHttpException('API is not enabled');
        }
    }

    /**
     * @throws \Exception
     */
    #[OA\Get(
        path: '/api/v3.0/categories',
        operationId: 'getCategories',
        tags: ['Public Endpoints']
    )]
    #[OA\Header(
        header: 'Accept-Language',
        description: 'The language code for the categories.',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns the the categories for the given language provided by "Accept-Language".',
        content: new OA\JsonContent(example: '
        [
            {
                "id": 1,
                "lang": "en",
                "parent_id": 0,
                "name": "Test",
                "description": "Hello, World! Hello, Tests!",
                "user_id": 1,
                "group_id": 1,
                "active": 1,
                "show_home": 1,
                "image": "category-1-en.png",
                "level": 1
              }
        ]')
    )]
    #[OA\Response(
        response: 404,
        description: 'If no categories are found for the given language.',
        content: new OA\JsonContent(example: []),
    )]
    public function list(): JsonResponse
    {
        $language = $this->container->get('phpmyfaq.language');
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $result = array_values($category->getAllCategories());

        if ($result === []) {
            return $this->json($result, Response::HTTP_NOT_FOUND);
        } else {
            return $this->json($result, Response::HTTP_OK);
        }
    }

    /**
     * @throws Exception|\JsonException
     */
    #[OA\Post(
        path: '/api/v3.0/category',
        operationId: 'createCategory',
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
        description: 'The parent category ID is a required value, the parent category name is optional. ' .
            'If the parent category name is present and the ID can be mapped, the parent category ID from the name ' .
            'will be used. If the parent category name cannot be mapped, a 409 error is thrown',
        required: true,
        content: new OA\MediaType(
            mediaType: 'application/json',
            schema: new OA\Schema(
                required: [
                    'language',
                    'parent-id',
                    'parent-category-name',
                    'category-name',
                    'description',
                    'user-id',
                    'group-id',
                    'is-active',
                    'show-on-homepage'
                ],
                properties: [
                    new OA\Property(property: 'language', type: 'string'),
                    new OA\Property(property: 'parent-id', type: 'integer'),
                    new OA\Property(property: 'parent-category-name', type: 'string'),
                    new OA\Property(property: 'category-name', type: 'string'),
                    new OA\Property(property: 'description', type: 'string'),
                    new OA\Property(property: 'user-id', type: 'integer'),
                    new OA\Property(property: 'group-id', type: 'integer'),
                    new OA\Property(property: 'is-active', type: 'boolean'),
                    new OA\Property(property: 'show-on-homepage', type: 'boolean')
                ],
                type: 'object'
            ),
            example: '{
                "language": "en",
                "parent-id": 1,
                "parent-category-name": "Test",
                "category-name": "Test 2",
                "description": "Hello, World! Hello, Tests!",
                "user-id": 1,
                "group-id": 1,
                "is-active": true,
                "show-on-homepage": true
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
        content: new OA\JsonContent(example: '{ "stored": false, "error": "Cannot add category" }')
    )]
    #[OA\Response(
        response: 409,
        description: 'If the parent category name cannot be mapped.',
        content: new OA\JsonContent(
            example: '{ "stored": false, "error": "The given parent category name was not found." }'
        )
    )]
    #[OA\Response(
        response: 401,
        description: 'If the user is not authenticated.'
    )]
    public function create(Request $request): JsonResponse
    {
        $this->hasValidToken();

        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $data = json_decode($request->getContent(), false, 512, JSON_THROW_ON_ERROR);

        $currentLanguage = $this->configuration->getLanguage()->getLanguage();

        $category = new Category($this->configuration, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $categoryPermission = new Permission($this->configuration);

        $languageCode = Filter::filterVar($data->language, FILTER_SANITIZE_SPECIAL_CHARS);
        $parentId = Filter::filterVar($data->{'parent-id'}, FILTER_VALIDATE_INT);
        if (isset($data->{'parent-category-name'})) {
            $parentCategoryName = Filter::filterVar($data->{'parent-category-name'}, FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $parentCategoryName = null;
        }

        $name = Filter::filterVar($data->{'category-name'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $description = Filter::filterVar($data->{'description'}, FILTER_SANITIZE_SPECIAL_CHARS);
        $userId = isset($data->{'user-id'}) ? Filter::filterVar($data->{'user-id'}, FILTER_VALIDATE_INT) : 1;
        $groupId = isset($data->{'group-id'}) ? Filter::filterVar($data->{'group-id'}, FILTER_VALIDATE_INT) : -1;
        $active = Filter::filterVar($data->{'is-active'}, FILTER_VALIDATE_BOOLEAN);
        $showOnHome = Filter::filterVar($data->{'show-on-homepage'}, FILTER_VALIDATE_BOOLEAN);

        // Check if the parent category name can be mapped
        if (!is_null($parentCategoryName)) {
            $parentCategoryIdFound = $category->getCategoryIdFromName($parentCategoryName);
            if ($parentCategoryIdFound === false) {
                $result = [
                    'stored' => false,
                    'error' => 'The given parent category name was not found.'
                ];
                return $this->json($result, Response::HTTP_CONFLICT);
            }

            $parentId = $parentCategoryIdFound;
        }

        $categoryData = new CategoryEntity();
        $categoryData
            ->setLang($languageCode)
            ->setParentId($parentId)
            ->setName($name)
            ->setDescription($description)
            ->setUserId($userId)
            ->setGroupId($groupId)
            ->setActive($active)
            ->setImage('')
            ->setShowHome($showOnHome);

        $categoryId = $category->create($categoryData);

        // Category Order entry
        $categoryOrder = new Order($this->configuration);
        $categoryOrder->add($categoryId, $parentId);

        if ($categoryId) {
            $categoryPermission->add(Permission::USER, [$categoryId], [-1]);
            $categoryPermission->add(Permission::GROUP, [$categoryId], [-1]);

            $result = [
                'stored' => true
            ];
            return $this->json($result, Response::HTTP_CREATED);
        } else {
            $result = [
                'stored' => false,
                'error' => 'Cannot add category'
            ];
            return $this->json($result, Response::HTTP_BAD_REQUEST);
        }
    }
}
