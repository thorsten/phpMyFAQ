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
 * @copyright 2023-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Category;
use phpMyFAQ\Category\CategoryImage;
use phpMyFAQ\Category\CategoryOrder;
use phpMyFAQ\Category\CategoryPermission;
use phpMyFAQ\Category\CategoryRelation;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\AbstractController;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    /**
     * @throws Exception
     */
    #[Route('admin/api/category/delete')]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);

        $configuration = Configuration::getConfigurationInstance();
        $currentUser = CurrentUser::getCurrentUser($configuration);

        $jsonResponse = new JsonResponse();
        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('category', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($currentUser);

        $category = new Category($configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryRelation = new CategoryRelation($configuration, $category);

        $categoryImage = new CategoryImage($configuration);
        $categoryImage->setFileName($category->getCategoryData($data->categoryId)->getImage());

        $categoryOrder = new CategoryOrder($configuration);
        $categoryOrder->remove($data->categoryId);

        $categoryPermission = new CategoryPermission($configuration);

        if (
            (
                is_countable($category->getCategoryLanguagesTranslated($data->categoryId))
                    ? count($category->getCategoryLanguagesTranslated($data->categoryId)) : 0
            ) === 1
        ) {
            $categoryPermission->delete(CategoryPermission::USER, [$data->categoryId]);
            $categoryPermission->delete(CategoryPermission::GROUP, [$data->categoryId]);
            $categoryImage->delete();
        }

        if (
            $category->delete($data->categoryId, $data->language) &&
            $categoryRelation->delete($data->categoryId, $data->language)
        ) {
            return $this->json(['success' => Translation::get('ad_categ_deleted')], Response::HTTP_OK);
        } else {
            return $this->json(
                ['error' => Translation::get('ad_adus_dberr') . $configuration->getDb()->error()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/category/permissions')]
    public function permissions(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $categoryPermission = new CategoryPermission(Configuration::getConfigurationInstance());

        $categoryData = $request->get('categories');

        if (empty($categoryData)) {
            $categories = [-1]; // Access for all users and groups
        } else {
            $categories = explode(',', (string) $categoryData);
        }

        return $this->json(
            [
                'user' => $categoryPermission->get(CategoryPermission::USER, $categories),
                'group' => $categoryPermission->get(CategoryPermission::GROUP, $categories)
            ],
            Response::HTTP_OK
        );
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/category/translations')]
    public function translations(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $configuration = Configuration::getConfigurationInstance();
        $category = new Category($configuration, [], false);

        $translations = $category->getCategoryLanguagesTranslated($request->get('categoryId'));

        return $this->json($translations, Response::HTTP_OK);
    }

    /**
     * @throws Exception
     */
    #[Route('admin/api/category/update-order')]
    public function updateOrder(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance()->verifyToken('category', $data->csrfToken)) {
            return $this->json(['error' => Translation::get('err_NotAuth')], Response::HTTP_UNAUTHORIZED);
        }

        $configuration = Configuration::getConfigurationInstance();
        $user = CurrentUser::getCurrentUser($configuration);

        [ $currentAdminUser, $currentAdminGroups ] = CurrentUser::getCurrentUserGroupId($user);

        $categoryOrder = new CategoryOrder($configuration);
        $categoryOrder->setCategoryTree($data->categoryTree);

        $parentId = $categoryOrder->getParentId($data->categoryTree, (int)$data->categoryId);

        $category = new Category($configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->updateParentCategory($data->categoryId, $parentId);

        return $this->json(['success' => Translation::get('ad_categ_save_order')], Response::HTTP_OK);
    }
}
