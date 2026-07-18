<?php

/**
 * Category repository interface for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-10-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Entity\CategoryEntity;

interface CategoryRepositoryInterface
{
    /**
     * Returns all categories ordered, optionally filtered by permission, activity, and language.
     *
     * @param int[]       $groups
     * @return array<int, array<string, mixed>>
     */
    public function findOrderedCategories(
        array $groups,
        int $userId,
        ?string $language,
        bool $withPermission = true,
        bool $withInactive = false,
    ): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findAllCategories(?string $language = null): array;

    /**
     * @return int[]
     */
    public function findAllCategoryIds(?string $language = null): array;

    public function findByIdAndLanguage(int $categoryId, string $language): ?CategoryEntity;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findCategoriesFromFaq(int $faqId, string $language): array;

    public function findCategoryIdByName(string $categoryName): ?int;

    public function create(CategoryEntity $categoryEntity): ?int;

    public function update(CategoryEntity $categoryEntity): bool;

    public function moveOwnership(int $currentOwner, int $newOwner): bool;

    public function hasLanguage(int $categoryId, string $categoryLanguage): bool;

    public function updateParentCategory(int $categoryId, int $parentId): bool;

    public function delete(int $categoryId, string $categoryLang): bool;

    /**
     * @return array<string, string>
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array;

    /**
     * Returns categories missing in the given language (used in the admin section).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findMissingCategories(?string $language = null): array;

    /**
     * Returns the number of categories that match name, language, and parent.
     */
    public function countByNameLangParent(string $name, string $lang, int $parentId): int;

    /**
     * Checks if a category has a link to a specific FAQ.
     */
    public function hasLinkToFaq(int $faqId, int $categoryId): bool;

    /**
     * Returns a page of categories, optionally filtered by language,
     * activity, and the user's group permissions.
     *
     * @param array<int> $groups
     * @return array<array-key, mixed>
     */
    /* @mago-expect lint:excessive-parameter-list - mirrors CategoryRepository::findCategoriesPaginated() */
    public function findCategoriesPaginated(
        ?string $language = null,
        int $limit = 25,
        int $offset = 0,
        string $sortField = 'id',
        string $sortOrder = 'ASC',
        bool $activeOnly = false,
        array $groups = [-1],
        int $userId = -1,
    ): array;

    /**
     * Returns the total number of categories matching the given filters.
     *
     * @param array<int> $groups
     */
    public function countCategories(
        ?string $language = null,
        bool $activeOnly = false,
        array $groups = [-1],
        int $userId = -1,
    ): int;
}
