<?php

declare(strict_types=1);

/**
 * The main admin category class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-10-12
 */

namespace phpMyFAQ\Administration;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

/**
 * Class Category
 *
 * All methods in this class only needed for admin category management.
 */
class Category
{
    private ?string $language = null;

    /** @var array<int> */
    public array $categories = [];

    /** @var array<string> */
    public array $categoryName = [];

    private array $children = [];

    private int $user = -1;

    /** @var int[] */
    private array $groups = [-1];

    /** @var array<int, int>> */
    private array $owner = [];

    /** @var array<int, int>> */
    private array $moderators = [];

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Returns all categories with ordered category IDs.
     *
     * @return array<int, array> Ordered categories
     */
    public function loadCategories(): array
    {
        $categories = [];
        $languageCheck = '';

        if ($this->getLanguage() !== null && preg_match("/^[a-z\-]{2,}$/", $this->getLanguage())) {
            $languageCheck .= sprintf(
                "AND fc.lang = '%s'",
                $this->configuration->getDb()->escape($this->getLanguage()),
            );
        }

        $query = sprintf(
            '
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
                %sfaqcategories fc
            LEFT JOIN
                %sfaqcategory_group fg
            ON
                fc.id = fg.category_id
            LEFT JOIN
                %sfaqcategory_order fco
            ON
                fc.id = fco.category_id
            LEFT JOIN
                %sfaqcategory_user fu
            ON
                fc.id = fu.category_id
            WHERE
                ( fg.group_id IN (%s)
            OR
                (fu.user_id = %d AND fg.group_id IN (%s)))
            %s
            GROUP BY
                fc.id, fc.lang, fc.parent_id, fc.name, fc.description, fc.user_id, fc.group_id, fc.active, fc.image, 
                fc.show_home, fco.position
            ORDER BY
                fco.position, fc.id ASC',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            implode(', ', $this->groups),
            $this->user,
            implode(', ', $this->groups),
            $languageCheck,
        );

        $result = $this->configuration->getDb()->query($query);

        if ($result) {
            while ($row = $this->configuration->getDb()->fetchArray($result)) {
                $this->categoryName[(int) $row['id']] = $row;
                $this->categories[(int) $row['id']] = $row;
                $this->children[(int) $row['parent_id']][(int) $row['id']] = &$this->categoryName[(int) $row['id']];
                $this->owner[(int) $row['id']] = &$row['user_id'];
                $this->moderators[(int) $row['id']] = &$row['group_id'];

                $categories[(int) $row['id']] = [
                    'id' => (int) $row['id'],
                    'lang' => $row['lang'],
                    'parent_id' => (int) $row['parent_id'],
                    'name' => $row['name'],
                    'description' => $row['description'],
                    'user_id' => (int) $row['user_id'],
                    'group_id' => (int) $row['group_id'],
                    'active' => (int) $row['active'],
                    'show_home' => (int) $row['show_home'],
                    'image' => $row['image'],
                    'level' => $this->getLevelOf((int) $row['id']),
                ];
            }

            foreach ($this->categoryName as $id) {
                $this->categoryName[$id['id']]['level'] = $this->getLevelOf($this->categoryName[$id['id']]['id']);
            }
        }

        return $categories;
    }

    /**
     * Creates the category tree for the admin category overview.
     */
    public function buildAdminCategoryTree(array $categories, int $parentId = 0): array
    {
        $result = [];

        foreach ($categories as $category) {
            if ($category['parent_id'] == $parentId) {
                $categoryId = $category['id'];
                $children = $this->buildAdminCategoryTree($categories, $categoryId);
                $result[$categoryId] = [];
            }
        }

        return $result;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): Category
    {
        $this->language = $language;

        return $this;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user = -1): Category
    {
        $this->user = $user;
        return $this;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): Category
    {
        $this->groups = $groups;
        return $this;
    }

    /**
     * Returns the user id of the category owner
     */
    public function getOwner(?int $categoryId = null): int
    {
        return $this->owner[$categoryId] ?? 1;
    }

    /**
     * Get the level of the item id.
     *
     * @param int $categoryId Entity id
     */
    private function getLevelOf(int $categoryId): int
    {
        $alreadyListed = [$categoryId];
        $level = 0;

        while (
            isset($this->categoryName[$categoryId]['parent_id'])
            && (int) $this->categoryName[$categoryId]['parent_id'] !== 0
        ) {
            ++$level;
            $categoryId = (int) $this->categoryName[$categoryId]['parent_id'];
            if (in_array($categoryId, $alreadyListed)) {
                break;
            } else {
                $alreadyListed[] = $categoryId;
            }
        }

        return $level;
    }
}
