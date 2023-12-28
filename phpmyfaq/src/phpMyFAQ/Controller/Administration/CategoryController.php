<?php

/**
 * The Admin Category Controller
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
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryOrder;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    #[Route('admin/api/category/permissions')]
    public function permissions(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $categoryPermission = new CategoryPermission(Configuration::getConfigurationInstance());

        $categoryData = $request->get('categories');

        if (empty($categoryData)) {
            $categories = [-1]; // Access for all users and groups
        } else {
            $categories = explode(',', $categoryData);
        }

        $response->setData(
            [
                'user' => $categoryPermission->get(CategoryPermission::USER, $categories),
                'group' => $categoryPermission->get(CategoryPermission::GROUP, $categories)
            ]
        );

        return $response;
    }

    #[Route('admin/api/category/translations')]
    public function translations(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $configuration = Configuration::getConfigurationInstance();
        $category = new Category($configuration, [], false);

        $translations = $category->getCategoryLanguagesTranslated($request->get('categoryId'));

        return $response->setData($translations);
    }

    #[Route('admin/api/category/update-order')]
    public function updateOrder(Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('category', $data->csrf)) {
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $response->setData(['error' => Translation::get('err_NotAuth')]);
            return $response;
        }

        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);
        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $category = new Category($configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryOrder = new CategoryOrder($configuration);

        $sortedData = array_filter($data->order, function ($element): bool {
            return is_numeric($element) ?? (int)$element;
        });

        $order = 1;
        foreach ($sortedData as $categoryId) {
            $currentPosition = $categoryOrder->getPositionById((int) $categoryId);

            if (!$currentPosition) {
                $categoryOrder->setPositionById((int) $categoryId, $order);
            } else {
                $categoryOrder->updatePositionById((int) $categoryId, $order);
            }
            $order++;
        }

        $response->setData(
            ['success' => Translation::get('ad_categ_save_order')]
        );
        return $response;
    }
}
