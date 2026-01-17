<?php

/**
 * Category repository implementation for phpMyFAQ.
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

use phpMyFAQ\Category\Permission\CategoryPermissionService;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Entity\CategoryEntity;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Small mapper to cast DB row to a normalized category array.
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'lang' => (string) $row['lang'],
            'parent_id' => (int) $row['parent_id'],
            'name' => (string) $row['name'],
            'description' => (string) $row['description'],
            'user_id' => (int) $row['user_id'],
            'group_id' => (int) $row['group_id'],
            'active' => (int) $row['active'],
            'show_home' => (int) $row['show_home'],
            'image' => (string) $row['image'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function findOrderedCategories(
        array $groups,
        int $userId,
        ?string $language,
        bool $withPermission = true,
        bool $withInactive = false,
    ): array {
        $where = '';

        if ($withPermission) {
            $categoryPermissionService = new CategoryPermissionService();
            $where = $withInactive
                ? $categoryPermissionService->buildWhereClauseWithInactive($groups, $userId)
                : $categoryPermissionService->buildWhereClause($groups, $userId);
        }

        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $where .= $where === '' ? ' WHERE' : ' AND';
            $where .= " fc.lang = '" . $this->configuration->getDb()->escape($language) . "'";
        }

        $prefix = Database::getTablePrefix();
        $query = <<<SQL
                SELECT
                    fc.id AS id,
                    fc.lang AS lang,
                    fc.parent_id AS parent_id,
                    fc.name AS name,
                    fc.description AS description,
                    fc.user_id AS user_id,
                    fc.group_id AS group_id,
                    fc.active AS active,
                    fc.image AS image,
                    fc.show_home AS show_home
                FROM
                    {$prefix}faqcategories fc
                LEFT JOIN {$prefix}faqcategory_group fg
                    ON fc.id = fg.category_id
                LEFT JOIN {$prefix}faqcategory_order fco
                    ON fc.id = fco.category_id
                LEFT JOIN {$prefix}faqcategory_user fu
                    ON fc.id = fu.category_id
                {$where}
                GROUP BY
                    fc.id, fc.lang, fc.parent_id, fc.name, fc.description, fc.user_id, fc.group_id, fc.active, fc.image,
                    fc.show_home, fco.position
                ORDER BY
                    fco.position, fc.id ASC
            SQL;

        $result = $this->configuration->getDb()->query($query);
        $categories = [];

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $mapped = $this->mapRow($row);
                $categories[$mapped['id']] = $mapped;
            }
        }

        return $categories;
    }

    /**
     * @inheritDoc
     */
    public function findAllCategories(?string $language = null): array
    {
        $categories = [];
        $prefix = Database::getTablePrefix();
        $query = sprintf(
            'SELECT id, lang, parent_id, name, description, user_id, group_id, active, show_home, image FROM %sfaqcategories',
            $prefix,
        );
        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $query .= " WHERE lang = '" . $this->configuration->getDb()->escape($language) . "'";
        }

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $mapped = $this->mapRow($row);
                $categories[$mapped['id']] = $mapped;
            }
        }

        return $categories;
    }

    /**
     * @inheritDoc
     */
    public function findAllCategoryIds(?string $language = null): array
    {
        $categories = [];

        $query = sprintf('SELECT id FROM %sfaqcategories', Database::getTablePrefix());

        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $query .= sprintf(" WHERE lang = '%s'", $this->configuration->getDb()->escape($language));
        }

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categories[] = (int) $row['id'];
        }

        return $categories;
    }

    /**
     * Find categories with pagination and sorting support.
     *
     * @param string|null $language Language code filter
     * @param int $limit Number of items per page
     * @param int $offset Starting offset
     * @param string $sortField Field to sort by
     * @param string $sortOrder Sort direction (ASC, DESC)
     * @return array
     */
    public function findCategoriesPaginated(
        ?string $language = null,
        int $limit = 25,
        int $offset = 0,
        string $sortField = 'id',
        string $sortOrder = 'ASC',
        bool $activeOnly = false,
    ): array {
        $categories = [];

        // Whitelist validation for the sort field
        $allowedSortFields = ['id', 'name', 'parent_id', 'active'];
        if (!in_array($sortField, $allowedSortFields, strict: true)) {
            $sortField = 'id';
        }

        $query = sprintf(
            'SELECT id, lang, parent_id, name, description, user_id, group_id, active, show_home, image FROM %sfaqcategories',
            Database::getTablePrefix(),
        );

        $whereConditions = [];

        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $whereConditions[] = "lang = '" . $this->configuration->getDb()->escape($language) . "'";
        }

        if ($activeOnly) {
            $whereConditions[] = 'active = 1';
        }

        if (!empty($whereConditions)) {
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
        }

        $query .= sprintf(' ORDER BY %s %s LIMIT %d OFFSET %d', $sortField, $sortOrder, $limit, $offset);

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $mapped = $this->mapRow($row);
                $categories[$mapped['id']] = $mapped;
            }
        }

        return $categories;
    }

    /**
     * Count total categories for a language.
     *
     * @param string|null $language Language code filter
     * @param bool $activeOnly Only count active categories
     * @return int Total count
     */
    public function countCategories(?string $language = null, bool $activeOnly = false): int
    {
        $query = sprintf('SELECT COUNT(*) as total FROM %sfaqcategories', Database::getTablePrefix());

        $whereConditions = [];

        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $whereConditions[] = "lang = '" . $this->configuration->getDb()->escape($language) . "'";
        }

        if ($activeOnly) {
            $whereConditions[] = 'active = 1';
        }

        if (!empty($whereConditions)) {
            $query .= ' WHERE ' . implode(' AND ', $whereConditions);
        }

        $result = $this->configuration->getDb()->query($query);
        $row = $this->configuration->getDb()->fetchObject($result);

        return (int) ($row->total ?? 0);
    }

    public function findByIdAndLanguage(int $categoryId, string $language): ?CategoryEntity
    {
        $categoryEntity = null;

        $query = sprintf(
            "SELECT * FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($language),
        );

        $result = $this->configuration->getDb()->query($query);

        if ($row = $this->configuration->getDb()->fetchObject($result)) {
            return new CategoryEntity()
                ->setId((int) $row->id)
                ->setLang($row->lang)
                ->setParentId((int) $row->parent_id)
                ->setName($row->name)
                ->setDescription($row->description)
                ->setUserId((int) $row->user_id)
                ->setGroupId((int) $row->group_id)
                ->setActive((bool) $row->active)
                ->setShowHome((bool) $row->show_home)
                ->setImage($row->image);
        }

        return $categoryEntity;
    }

    /**
     * @inheritDoc
     */
    public function findCategoriesFromFaq(int $faqId, string $language): array
    {
        $query = sprintf(
            "
            SELECT
                fc.id AS id,
                fc.lang AS lang,
                fc.parent_id AS parent_id,
                fc.name AS name,
                fc.description AS description
            FROM
                %sfaqcategoryrelations fcr,
                %sfaqcategories fc
            WHERE
                fc.id = fcr.category_id
            AND
                fcr.record_id = %d
            AND
                fcr.category_lang = '%s'
            AND
                fc.lang = '%s'",
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $faqId,
            $this->configuration->getDb()->escape($language),
            $this->configuration->getDb()->escape($language),
        );

        $result = $this->configuration->getDb()->query($query);
        $categories = [];
        if ($this->configuration->getDb()->numRows($result) > 0) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $categories[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'lang' => (string) $row['lang'],
                    'parent_id' => (int) $row['parent_id'],
                    'name' => (string) $row['name'],
                    'description' => (string) $row['description'],
                ];
            }
        }

        return $categories;
    }

    public function findCategoryIdByName(string $categoryName): ?int
    {
        $query = sprintf(
            "SELECT id FROM %sfaqcategories WHERE name = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($categoryName),
        );

        $result = $this->configuration->getDb()->query($query);
        if ($this->configuration->getDb()->numRows($result) > 0) {
            return (int) $this->configuration->getDb()->fetchRow($result);
        }

        return null;
    }

    public function create(CategoryEntity $categoryEntity): ?int
    {
        if ($categoryEntity->getId() === 0) {
            $categoryEntity->setId($this->configuration->getDb()->nextId(
                Database::getTablePrefix() . 'faqcategories',
                column: 'id',
            ));
        }

        $query = sprintf(
            "INSERT INTO    %sfaqcategories(id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)    VALUES(%d, '%s', %d, '%s', '%s', %d, %d, %d, '%s', %d)",
            Database::getTablePrefix(),
            $categoryEntity->getId(),
            $this->configuration->getDb()->escape($categoryEntity->getLang()),
            $categoryEntity->getParentId(),
            $this->configuration->getDb()->escape($categoryEntity->getName()),
            $this->configuration->getDb()->escape($categoryEntity->getDescription()),
            $categoryEntity->getUserId(),
            $categoryEntity->getGroupId(),
            $categoryEntity->getActive(),
            $this->configuration->getDb()->escape($categoryEntity->getImage()),
            $categoryEntity->getShowHome(),
        );

        $this->configuration->getDb()->query($query);

        return $categoryEntity->getId();
    }

    public function update(CategoryEntity $categoryEntity): bool
    {
        $query = sprintf(
            "UPDATE %sfaqcategories SET name = '%s', description = '%s', user_id = %d, group_id = %d, active = %d, show_home = %d, image = '%s' WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($categoryEntity->getName()),
            $this->configuration->getDb()->escape($categoryEntity->getDescription()),
            $categoryEntity->getUserId(),
            $categoryEntity->getGroupId(),
            $categoryEntity->getActive(),
            $categoryEntity->getShowHome(),
            $this->configuration->getDb()->escape($categoryEntity->getImage()),
            $categoryEntity->getId(),
            $this->configuration->getDb()->escape($categoryEntity->getLang()),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function moveOwnership(int $currentOwner, int $newOwner): bool
    {
        $query = sprintf(
            'UPDATE %sfaqcategories SET user_id = %d WHERE user_id = %d',
            Database::getTablePrefix(),
            $newOwner,
            $currentOwner,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function hasLanguage(int $categoryId, string $categoryLanguage): bool
    {
        $query = sprintf(
            "SELECT lang FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($categoryLanguage),
        );

        $result = $this->configuration->getDb()->query($query);

        return $this->configuration->getDb()->numRows($result) > 0;
    }

    public function updateParentCategory(int $categoryId, int $parentId): bool
    {
        $query = sprintf(
            'UPDATE %sfaqcategories SET parent_id = %d WHERE id = %d',
            Database::getTablePrefix(),
            $parentId,
            $categoryId,
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    public function delete(int $categoryId, string $categoryLang): bool
    {
        $query = sprintf(
            "DELETE FROM %sfaqcategories WHERE id = %d AND lang = '%s'",
            Database::getTablePrefix(),
            $categoryId,
            $this->configuration->getDb()->escape($categoryLang),
        );

        return (bool) $this->configuration->getDb()->query($query);
    }

    /**
     * @inheritDoc
     */
    public function getCategoryLanguagesTranslated(int $categoryId): array
    {
        $existingLanguages = $this->configuration->getLanguage()->isLanguageAvailable($categoryId, 'faqcategories');

        $translated = [];
        foreach ($existingLanguages as $existingLanguage) {
            $whereParts = [];
            if ($categoryId !== 0) {
                $whereParts[] = 'id = ' . (int) $categoryId;
            }

            $whereParts[] = "lang = '" . $this->configuration->getDb()->escape($existingLanguage) . "'";
            $query = sprintf(
                'SELECT name, description FROM %sfaqcategories WHERE %s',
                Database::getTablePrefix(),
                implode(separator: ' AND ', array: $whereParts),
            );

            $result = $this->configuration->getDb()->query($query);
            if ($row = $this->configuration->getDb()->fetchArray($result)) {
                $translated[$existingLanguage] =
                    $row['name'] . ($row['description'] === '' ? '' : '  (' . $row['description'] . ')');
            }
        }

        ksort($translated);

        return $translated;
    }

    public function findMissingCategories(?string $language = null): array
    {
        $query = sprintf(
            'SELECT id, lang, parent_id, name, description, user_id, group_id, active FROM %sfaqcategories',
            Database::getTablePrefix(),
        );
        if ($language !== null && preg_match(pattern: '/^[a-z\-]{2,}$/', subject: $language)) {
            $query .= " WHERE lang != '" . $this->configuration->getDb()->escape($language) . "'";
        }

        $query .= ' ORDER BY id';

        $result = $this->configuration->getDb()->query($query);
        $categories = [];
        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categories[] = [
                'id' => (int) $row['id'],
                'lang' => (string) $row['lang'],
                'parent_id' => (int) $row['parent_id'],
                'name' => (string) $row['name'],
                'description' => (string) $row['description'],
                'user_id' => (int) $row['user_id'],
                'group_id' => (int) $row['group_id'],
                'active' => (int) $row['active'],
            ];
        }

        return $categories;
    }

    public function countByNameLangParent(string $name, string $lang, int $parentId): int
    {
        $query = sprintf(
            "SELECT COUNT(*) AS cnt FROM %sfaqcategories WHERE name = '%s' AND lang = '%s' AND parent_id = %d",
            Database::getTablePrefix(),
            $this->configuration->getDb()->escape($name),
            $this->configuration->getDb()->escape($lang),
            $parentId,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchArray($result);
            if ($row && isset($row['cnt'])) {
                return (int) $row['cnt'];
            }
        }

        return 0;
    }

    /**
     * Checks if a category has a link to a specific FAQ.
     */
    public function hasLinkToFaq(int $faqId, int $categoryId): bool
    {
        $query = sprintf(
            'SELECT COUNT(*) AS cnt FROM %sfaqcategoryrelations WHERE category_id = %d AND record_id = %d',
            Database::getTablePrefix(),
            $categoryId,
            $faqId,
        );

        $result = $this->configuration->getDb()->query($query);
        if ($result) {
            $row = $this->configuration->getDb()->fetchArray($result);
            if ($row && isset($row['cnt'])) {
                return (int) $row['cnt'] > 0;
            }
        }

        return false;
    }
}
