<?php

declare(strict_types=1);

/**
 * Category repository implementation for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    phpMyFAQ Team
 * @copyright 2004-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 */

namespace phpMyFAQ\Category;

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
            $groupsList = $groups === [] ? '-1' : implode(', ', $groups);
            $activeClause = $withInactive ? '' : 'AND fc.active = 1';
            $where = "WHERE ( fg.group_id IN ($groupsList) OR (fu.user_id = $userId AND fg.group_id IN ($groupsList))) $activeClause";
        }

        if ($language !== null && preg_match('/^[a-z\-]{2,}$/', $language)) {
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
                $categories[(int) $row['id']] = [
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
        $query = <<<SQL
            SELECT 
                id, lang, parent_id, name, description, user_id, group_id, active, show_home, image 
            FROM 
                {$prefix}faqcategories
        SQL;

        if ($language !== null && preg_match('/^[a-z\-]{2,}$/', $language)) {
            $query .= " WHERE lang = '" . $this->configuration->getDb()->escape($language) . "'";
        }

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $categories[(int) $row['id']] = [
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

        if ($language !== null && preg_match('/^[a-z\-]{2,}$/', $language)) {
            $query .= sprintf(" WHERE lang = '%s'", $this->configuration->getDb()->escape($language));
        }

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $categories[] = (int) $row['id'];
        }

        return $categories;
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
            $categoryEntity = (new CategoryEntity())
                ->setId($row->id)
                ->setLang($row->lang)
                ->setParentId($row->parent_id)
                ->setName($row->name)
                ->setDescription($row->description)
                ->setUserId($row->user_id)
                ->setGroupId($row->group_id)
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
            "\n            SELECT\n                fc.id AS id,\n                fc.lang AS lang,\n                fc.parent_id AS parent_id,\n                fc.name AS name,\n                fc.description AS description\n            FROM\n                %sfaqcategoryrelations fcr,\n                %sfaqcategories fc\n            WHERE\n                fc.id = fcr.category_id\n            AND\n                fcr.record_id = %d\n            AND\n                fcr.category_lang = '%s'\n            AND\n                fc.lang = '%s'",
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
                'id',
            ));
        }

        $query = sprintf(
            "\n            INSERT INTO\n                %sfaqcategories\n            (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)\n                VALUES\n            (%d, '%s', %d, '%s', '%s', %d, %d, %d, '%s', %d)",
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
            "\n            UPDATE\n                %sfaqcategories\n            SET\n                name = '%s',\n                description = '%s',\n                user_id = %d,\n                group_id = %d,\n                active = %d,\n                show_home = %d,\n                image = '%s'\n            WHERE\n                id = %d\n            AND\n                lang = '%s'",
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
                implode(' AND ', $whereParts),
            );

            $result = $this->configuration->getDb()->query($query);
            if ($row = $this->configuration->getDb()->fetchArray($result)) {
                $translated[$existingLanguage] =
                    $row['name'] . ('' == $row['description'] ? '' : '  (' . $row['description'] . ')');
            }
        }

        ksort($translated);

        return $translated;
    }

    public function findMissingCategories(?string $language = null): array
    {
        $query = sprintf(
            format: 'SELECT id, lang, parent_id, name, description, user_id, group_id, active FROM %sfaqcategories',
            values: Database::getTablePrefix(),
        );
        if (
            $language !== null
            && preg_match(
                pattern: '/^[a-z\-]{2,}$/',
                subject: $language,
            )
        ) {
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
}
