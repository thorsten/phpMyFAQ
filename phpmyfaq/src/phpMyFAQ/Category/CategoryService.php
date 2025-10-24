<?php

/**
 * Category service for CRUD and repository operations.
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
 * @since     2025-10-20
 */

declare(strict_types=1);

namespace phpMyFAQ\Category;

use phpMyFAQ\Category\CategoryRepositoryInterface;
use phpMyFAQ\Entity\CategoryEntity;

/**
 * Service for category CRUD and repository operations.
 * Consolidates all repository delegation methods from the legacy Category class.
 */
final class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {
    }

    /**
     * Gets all categories.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAllCategories(?string $language = null): array
    {
        return $this->repository->findAllCategories($language);
    }

    /**
     * Gets all category IDs.
     *
     * @return array<int>
     */
    public function getAllCategoryIds(?string $language = null): array
    {
        return $this->repository->findAllCategoryIds($language);
    }

    /**
     * Gets categories from a FAQ.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategoriesFromFaq(int $faqId, string $language): array
    {
        return $this->repository->findCategoriesFromFaq($faqId, $language);
    }

    /**
     * Gets the first category ID from a FAQ.
     */
    public function getCategoryIdFromFaq(int $faqId, string $language): int
    {
        $categories = $this->getCategoriesFromFaq($faqId, $language);
        $ids = array_keys($categories);
        return $ids[0] ?? 0;
    }

    /**
     * Gets all category IDs from a FAQ.
     *
     * @return array<int>
     */
    public function getCategoryIdsFromFaq(int $faqId, string $language): array
    {
        $categories = $this->getCategoriesFromFaq($faqId, $language);
        return array_keys($categories);
    }

    /**
     * Gets category ID from name.
     */
    public function getCategoryIdFromName(string $categoryName): int|bool
    {
        $id = $this->repository->findCategoryIdByName($categoryName);
        return $id ?? false;
    }

    /**
     * Gets category data by ID and language.
     */
    public function getCategoryData(int $categoryId, string $language): CategoryEntity
    {
        $entity = $this->repository->findByIdAndLanguage($categoryId, $language);
        return $entity ?? new CategoryEntity();
    }

    /**
     * Creates a new category.
     */
    public function create(CategoryEntity $categoryEntity): ?int
    {
        return $this->repository->create($categoryEntity);
    }

    /**
     * Updates an existing category.
     */
    public function update(CategoryEntity $categoryEntity): bool
    {
        return $this->repository->update($categoryEntity);
    }

    /**
     * Deletes a category.
     */
    public function delete(int $categoryId, string $categoryLang): bool
    {
        return $this->repository->delete($categoryId, $categoryLang);
    }

    /**
     * Moves ownership from one user to another.
     */
    public function moveOwnership(int $currentOwner, int $newOwner): bool
    {
        return $this->repository->moveOwnership($currentOwner, $newOwner);
    }

    /**
     * Checks if a category has a specific language.
     */
    public function hasLanguage(int $categoryId, string $categoryLanguage): bool
    {
        return $this->repository->hasLanguage($categoryId, $categoryLanguage);
    }

    /**
     * Updates the parent category.
     */
    public function updateParentCategory(int $categoryId, int $parentId): bool
    {
        if ($categoryId === $parentId) {
            return false;
        }
        return $this->repository->updateParentCategory($categoryId, $parentId);
    }

    /**
     * Checks if a category exists.
     */
    public function checkIfCategoryExists(CategoryEntity $categoryEntity): int
    {
        return $this->repository->countByNameLangParent(
            $categoryEntity->getName(),
            $categoryEntity->getLang(),
            $categoryEntity->getParentId(),
        );
    }

    /**
     * Gets category languages translated.
     *
     * @return array<string, string>
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        return $this->repository->getCategoryLanguagesTranslated($categoryId);
    }

    /**
     * Gets missing categories.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getMissingCategories(?string $language = null): array
    {
        return $this->repository->findMissingCategories($language);
    }
}
