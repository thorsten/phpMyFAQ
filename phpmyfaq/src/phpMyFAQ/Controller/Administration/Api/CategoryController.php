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
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-10-27
 */

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Category;
use phpMyFAQ\Category\Permission;
use phpMyFAQ\Category\Relation;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AdminLogType;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Filter;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractAdministrationApiController
{
    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'category/delete', name: 'admin.api.category.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('category', $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryRelation = new Relation($this->configuration, $category);

        $categoryImage = $this->container->get(id: 'phpmyfaq.category.image');
        $categoryImage->setFileName($category->getCategoryData((int) $data->categoryId)->getImage());

        $categoryOrder = $this->container->get(id: 'phpmyfaq.category.order');
        $categoryOrder->remove((int) $data->categoryId);

        $categoryPermission = $this->container->get(id: 'phpmyfaq.category.permission');

        if (
            (
                is_countable($category->getCategoryLanguagesTranslated((int) $data->categoryId))
                    ? count($category->getCategoryLanguagesTranslated((int) $data->categoryId))
                    : 0
            ) === 1
        ) {
            $categoryPermission->delete(Permission::USER, [(int) $data->categoryId]);
            $categoryPermission->delete(Permission::GROUP, [(int) $data->categoryId]);
            $categoryImage->delete();
        }

        if (
            $category->delete((int) $data->categoryId, $data->language)
            && $categoryRelation->delete((int) $data->categoryId, $data->language)
        ) {
            $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_DELETE->value . ':' . $data->categoryId);

            return $this->json(['success' => Translation::get(key: 'ad_categ_deleted')], Response::HTTP_OK);
        }

        return $this->json([
            'error' => Translation::get(key: 'ad_adus_dberr') . $this->configuration->getDb()->error(),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route(path: 'category/permissions', name: 'admin.api.category.permissions', methods: ['GET'])]
    public function permissions(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $categoryPermission = $this->container->get(id: 'phpmyfaq.category.permission');

        $categoryData = $request->attributes->get('categories');

        if (in_array($categoryData, [null, '', false], true)) {
            $categories = [-1]; // Access for all users and groups
        }

        if (!in_array($categoryData, [null, '', false], true)) {
            $categories = explode(',', (string) $categoryData);
        }

        if (!in_array(true, filter_var_array($categories, FILTER_VALIDATE_INT), strict: true)) {
            return $this->json(['error' => 'Only integer values are valid.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->json([
            'user' => $categoryPermission->get(Permission::USER, $categories),
            'group' => $categoryPermission->get(Permission::GROUP, $categories),
        ], Response::HTTP_OK);
    }

    #[Route(path: 'category/translations', name: 'admin.api.category.translations', methods: ['GET'])]
    public function translations(Request $request): JsonResponse
    {
        $this->userIsAuthenticated();

        $category = new Category($this->configuration, [], false);

        $categoryId = (int) Filter::filterVar($request->attributes->get('categoryId'), FILTER_VALIDATE_INT);

        $translations = $category->getCategoryLanguagesTranslated($categoryId);

        return $this->json($translations, Response::HTTP_OK);
    }

    /**
     * @throws \Exception
     */
    #[Route(path: 'category/update-order', name: 'admin.api.category.update-order', methods: ['POST'])]
    public function updateOrder(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $data = json_decode($request->getContent());

        if (!Token::getInstance($this->session)->verifyToken('category', $data->csrfToken)) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $categoryOrder = $this->container->get(id: 'phpmyfaq.category.order');
        $categoryOrder->setCategoryTree($data->categoryTree);

        $parentId = $categoryOrder->getParentId($data->categoryTree, (int) $data->categoryId);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->updateParentCategory((int) $data->categoryId, $parentId);

        $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_REORDER->value . ':' . $data->categoryId);

        return $this->json(['success' => Translation::get(key: 'ad_categ_save_order')], Response::HTTP_OK);
    }
}
