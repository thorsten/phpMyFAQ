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
 * @copyright 2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-07-29
 */

namespace phpMyFAQ\Api\Controller;

use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Language;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class CategoryController
{
    public function list(): JsonResponse
    {
        $response = new JsonResponse();
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

        if (count($result) === 0) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        } else {
            $response->setStatusCode(Response::HTTP_OK);
        }
        $response->setData($result);

        return $response;
    }
}
