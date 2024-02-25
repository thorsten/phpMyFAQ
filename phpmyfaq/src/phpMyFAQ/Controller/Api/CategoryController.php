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
use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CategoryController
{
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
        $jsonResponse = new JsonResponse();
        $faqConfig = Configuration::getConfigurationInstance();
        $language = new Language($faqConfig);
        $currentLanguage = $language->setLanguageByAcceptLanguage();

        $user = CurrentUser::getCurrentUser($faqConfig);
        [ $currentUser, $currentGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $category = new Category($faqConfig, $currentGroups, true);
        $category->setUser($currentUser);
        $category->setGroups($currentGroups);
        $category->setLanguage($currentLanguage);

        $result = array_values($category->getAllCategories());

        if ($result === []) {
            $jsonResponse->setStatusCode(Response::HTTP_NOT_FOUND);
        } else {
            $jsonResponse->setStatusCode(Response::HTTP_OK);
        }

        $jsonResponse->setData($result);

        return $jsonResponse;
    }
}
