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
use phpMyFAQ\Category\Image;
use phpMyFAQ\Category\Order;
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
    public function __construct(
        private readonly Image $categoryImage,
        private readonly Order $categoryOrder,
        private readonly Permission $categoryPermission,
    ) {
        parent::__construct();
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[Route(path: 'category/delete', name: 'admin.api.category.delete', methods: ['DELETE'])]
    public function delete(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_DELETE);

        $data = $this->getJsonObject($request);

        if (!Token::getInstance($this->session)->verifyToken('category', (string) ($data->csrfToken ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        $categoryLang = (string) ($data->language ?? '');

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);

        $categoryRelation = new Relation($this->configuration, $category);

        $this->categoryImage->setFileName($category->getCategoryData($categoryId)->getImage() ?? '');

        $this->categoryOrder->remove($categoryId);

        if (count($category->getCategoryLanguagesTranslated($categoryId)) === 1) {
            $this->categoryPermission->delete(Permission::USER, [$categoryId]);
            $this->categoryPermission->delete(Permission::GROUP, [$categoryId]);
            $this->categoryImage->delete();
        }

        if ($category->delete($categoryId, $categoryLang) && $categoryRelation->delete($categoryId, $categoryLang)) {
            $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_DELETE->value . ':' . $categoryId);

            return $this->json(['success' => Translation::get(key: 'ad_categ_deleted')], Response::HTTP_OK);
        }

        $this->configuration->getLogger()->error('Failed to delete category', [
            'categoryId' => $categoryId,
            'sqlError' => $this->configuration->getDb()->error(),
        ]);

        return $this->json([
            'error' => Translation::get(key: 'ad_adus_dberr'),
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    #[Route(
        path: 'category/permissions/{categories}',
        name: 'admin.api.category.permissions',
        methods: ['GET'],
        defaults: ['categories' => null],
    )]
    public function permissions(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

        $categoryData = $request->attributes->get('categories');

        // Access for all users and groups unless specific categories are requested
        $categories = [-1];
        if (!in_array($categoryData, [null, '', false], strict: true)) {
            $rawCategories = explode(',', (string) $categoryData);
            $validatedCategories = filter_var_array($rawCategories, FILTER_VALIDATE_INT);
            if (!is_array($validatedCategories) || in_array(false, $validatedCategories, strict: true)) {
                return $this->json(['error' => 'Only integer values are valid.'], Response::HTTP_BAD_REQUEST);
            }

            $categories = array_map(static fn(mixed $categoryId): int => (int) $categoryId, $validatedCategories);
        }

        return $this->json([
            'user' => $this->categoryPermission->get(Permission::USER, $categories),
            'group' => $this->categoryPermission->get(Permission::GROUP, $categories),
        ], Response::HTTP_OK);
    }

    #[Route(path: 'category/translations/{categoryId}', name: 'admin.api.category.translations', methods: ['GET'])]
    public function translations(Request $request): JsonResponse
    {
        $this->userHasPermission(PermissionType::CATEGORY_EDIT);

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

        $data = $this->getJsonObject($request);

        if (!Token::getInstance($this->session)->verifyToken('category', (string) ($data->csrfToken ?? ''))) {
            return $this->json(['error' => Translation::get(key: 'msgNoPermission')], Response::HTTP_UNAUTHORIZED);
        }

        $categoryId = (int) ($data->categoryId ?? 0);
        $categoryTreeRaw = $data->categoryTree ?? [];
        $categoryTree = array_values(array_filter(
            is_array($categoryTreeRaw) ? $categoryTreeRaw : [],
            static fn(mixed $node): bool => $node instanceof \stdClass,
        ));

        [$currentAdminUser, $currentAdminGroups] = CurrentUser::getCurrentUserGroupId($this->currentUser);

        $this->categoryOrder->setCategoryTree($categoryTree);

        $parentId = $this->categoryOrder->getParentId($categoryTree, $categoryId);

        $category = new Category($this->configuration, [], false);
        $category->setUser($currentAdminUser);
        $category->setGroups($currentAdminGroups);
        $category->updateParentCategory($categoryId, $parentId ?? 0);

        $this->adminLog->log($this->currentUser, AdminLogType::CATEGORY_REORDER->value . ':' . $categoryId);

        return $this->json(['success' => Translation::get(key: 'ad_categ_save_order')], Response::HTTP_OK);
    }
}
