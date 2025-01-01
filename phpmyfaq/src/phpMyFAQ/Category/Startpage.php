<?php

/**
 * Startpage category class.
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
 * @since     2024-04-01
 */

namespace phpMyFAQ\Category;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

readonly class Startpage
{
    private int $user;

    private array $groups;

    private string $language;

    public function __construct(private Configuration $configuration)
    {
    }

    public function setUser(int $user): Startpage
    {
        $this->user = $user;
        return $this;
    }

    public function setGroups(array $groups): Startpage
    {
        $this->groups = $groups;
        return $this;
    }

    public function setLanguage(string $language): Startpage
    {
        $this->language = $language;
        return $this;
    }

    /**
     * Gets all categories and write them in an array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCategories(): array
    {
        $categories = [];
        $where = '';

        if (preg_match("/^[a-z\-]{2,}$/", $this->language)) {
            $where = "AND fc.lang = '" . $this->configuration->getDb()->escape($this->language) . "'";
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
                fc.show_home AS show_home,
                fco.position AS position
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
            AND
                fc.active = 1
            AND
                fc.show_home = 1
                %s
            GROUP BY
                fc.id, fc.lang, fc.name, fc.description, fc.user_id, fc.group_id, fc.active, fc.image, 
                fc.show_home, fco.position
            ORDER BY
                fco.position, fc.id ASC

                ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            implode(', ', $this->groups),
            $this->user,
            implode(', ', $this->groups),
            $where
        );

        $result = $this->configuration->getDb()->query($query);

        while ($row = $this->configuration->getDb()->fetchArray($result)) {
            $url = sprintf('%sindex.php?action=show&cat=%d', $this->configuration->getDefaultUrl(), $row['id']);
            $link = new Link($url, $this->configuration);
            $link->itemTitle = $row['name'];
            $image = '' === $row['image'] ? '' : 'content/user/images/' . $row['image'];

            $category = [
                'url' => $link->toString(),
                'name' => $row['name'],
                'description' => $row['description'],
                'image' => $image
            ];

            $categories[] = $category;
        }

        return $categories;
    }
}
