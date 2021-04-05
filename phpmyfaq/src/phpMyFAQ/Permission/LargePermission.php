<?php

/**
 * The large permission class provides section rights for groups and users.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

namespace phpMyFAQ\Permission;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\User;
use phpMyFAQ\User\CurrentUser;

/**
 * Class LargePermission
 *
 * @package phpMyFAQ\Permission
 */
class LargePermission extends MediumPermission
{
    /**
     * Default data for new sections.
     *
     * @var array<string>
     */
    public $defaultSectionData = [
        'name' => 'DEFAULT_SECTION',
        'description' => 'Short section description.',
    ];

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Returns true, if the user given by $userId owns the right
     * specified by $right in a section. It does not matter if
     * the user owns this right as a user-right or because of a
     * group-membership in a section. The parameter $right may
     * be a right-ID (recommended for performance) or a right-name.
     *
     * @param int   $userId
     * @param mixed $right
     *
     * @return bool
     */
    public function hasPermission(int $userId, $right): bool
    {
        $user = new CurrentUser($this->config);
        $user->getUserById($userId);

        if ($user->isSuperAdmin()) {
            return true;
        }

        // get right id
        if (!is_numeric($right) && is_string($right)) {
            $right = $this->getRightId($right);
        }

        // check user right, group right and section right
        if (
            $this->checkUserSectionRight($userId, $right)
            || $this->checkUserGroupRight($userId, $right)
            || $this->checkUserRight($userId, $right)
        ) {
            return true;
        }
        return false;
    }

    /**
     * Returns true if the user $userId owns the right $rightId
     * because of a section membership, otherwise false.
     *
     * @param int $userId
     * @param int $rightId
     *
     * @return bool
     */
    public function checkUserSectionRight(int $userId, int $rightId): bool
    {
        if ($userId < 0 || !is_numeric($userId) || $rightId < 0 || !is_numeric($rightId)) {
            return false;
        }
        $select = sprintf(
            '
            SELECT
                fgr.right_id
            FROM 
                %sfaquser_group fug
            LEFT JOIN
                %sfaqgroup_right fgr
            ON
                fgr.group_id = fug.group_id
            WHERE 
                fug.user_id = %d
            AND
                fgr.right_id = %d
            ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId,
            $rightId
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return false;
        }
        if ($this->config->getDb()->numRows($res) > 0) {
            return true;
        }
        return false;
    }

    /**
     * Adds a new section to the database and returns the ID of the
     * new section. The associative array $sectionData contains the
     * data for the new section.
     *
     * @param array<string> $sectionData Array of section data
     *
     * @return int
     */
    public function addSection(array $sectionData): int
    {
        // check if section already exists
        if ($this->getSectionId($sectionData['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(Database::getTablePrefix() . 'faqsections', 'id');
        $sectionData = $this->checkSectionData($sectionData);
        $insert = sprintf(
            "
            INSERT INTO
                %sfaqsections
            (id, name, description)
                VALUES
            (%d, '%s', '%s')",
            Database::getTablePrefix(),
            $nextId,
            $sectionData['name'],
            $sectionData['description']
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return 0;
        }

        return $nextId;
    }

    /**
     * Returns the ID of the section that has the name $name. Returns
     * 0 if the section name cannot be found.
     *
     * @param  string $name
     * @return int
     */
    public function getSectionId(string $name): int
    {
        $select = sprintf(
            '
            SELECT 
                    id
            FROM 
                %sfaqsections
            WHERE 
                name = %s',
            Database::getTablePrefix(),
            $name
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return 0;
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row['id'];
    }

    /**
     * Checks the given associative array $sectionData. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->defaultSectionData.
     * Returns the corrected $sectionData associative array.
     *
     * @param array<string> $sectionData
     * @return array<string>
     */
    public function checkSectionData(array $sectionData): array
    {
        if (!isset($sectionData['name']) || !is_string($sectionData['name'])) {
            $sectionData['name'] = $this->defaultSectionData['name'];
        }
        if (!isset($sectionData['description']) || !is_string($sectionData['description'])) {
            $sectionData['description'] = $this->defaultSectionData['description'];
        }

        return $sectionData;
    }

    /**
     * Changes the section data of the given section.
     *
     * @param  int $sectionId
     * @param  array<string> $sectionData
     * @return bool
     */
    public function changeSection(int $sectionId, array $sectionData): bool
    {
        $checkedData = $this->checkSectionData($sectionData);
        $set = '';
        $comma = '';

        foreach ($sectionData as $key => $val) {
            $set .= $comma . $key . " = '" . $this->config->getDb()->escape($checkedData[$key]) . "'";
            $comma = ",\n                ";
        }

        $update = sprintf(
            '
            UPDATE
                %sfaqsections
            SET
                %s
            WHERE
                id = %d',
            Database::getTablePrefix(),
            $set,
            $sectionId
        );

        $res = $this->config->getDb()->query($update);

        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Removes the section given by $sectionId from the database.
     * Returns true on success, otherwise false.
     *
     * @param  int $sectionId
     * @return bool
     */
    public function deleteSection(int $sectionId): bool
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsections
            WHERE
                id = %d',
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_group
            WHERE
                section_id = %d',
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_news
            WHERE
                section_id = %d',
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns an array that contains the group IDs of all groups
     * of the section $sectionId.
     *
     * @param  int $sectionId
     * @return array<int>
     */
    public function getSectionGroups(int $sectionId): array
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return [];
        }

        $select = sprintf(
            '
            SELECT 
                %sfaqsection_group.group_id
            FROM
                %sfaqsection_group
            WHERE 
                %sfaqsection_group.section_id = %d
            ',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($select);

        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['group_id'];
        }

        return $result;
    }

    /**
     * Adds a new group $groupId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $groupId
     * @param  int $sectionId
     * @return bool
     */
    public function addGroupToSection(int $groupId, int $sectionId): bool
    {
        if ($sectionId <= 0 || !is_numeric($sectionId) | $groupId <= 0 || !is_numeric($groupId)) {
            return false;
        }

        $select = sprintf(
            '
            SELECT 
                group_id
            FROM
                %sfaqsection_group
            WHERE 
                section_id = %d
            AND 
                group_id = %d
            ',
            Database::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($select);

        if ($this->config->getDb()->numRows($res) > 0) {
            return false;
        }

        $insert = sprintf(
            '
            INSERT INTO
                %sfaqsection_group
            (section_id, group_id)
               VALUES
            (%d, %d)',
            Database::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Removes all groups from the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $sectionId
     * @return bool
     */
    public function removeAllGroupsFromSection(int $sectionId): bool
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return false;
        }

        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_group
            WHERE
                section_id = %d',
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns an associative array with the section data of the section
     * $sectionId.
     *
     * @param  int $sectionId
     * @return array<string>
     */
    public function getSectionData(int $sectionId): array
    {
        $select = sprintf(
            '
            SELECT 
                    *
            FROM 
                %sfaqsections
            WHERE 
                id = %d',
            Database::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return [];
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row;
    }

    /**
     * Returns an array with the IDs of all sections stored in the
     * database if no user ID is passed.
     *
     * @param  int $userId
     * @return array<int>
     */
    public function getAllSections(int $userId = -1): array
    {
        if ($userId !== -1) {
            return $this->getUserSections($userId);
        }

        $select = sprintf('SELECT * FROM %sfaqsections', Database::getTablePrefix());

        $res = $this->config->getDb()->query($select);
        if (!$res || $this->config->getDb()->numRows($res) < 1) {
            return [];
        }
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains the IDs of all sections in which
     * the user $userId is a member.
     *
     * @param  int $userId
     * @return array<int>
     */
    public function getUserSections(int $userId): array
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return [-1];
        }

        $select = sprintf(
            '
            SELECT 
                fsg.section_id
            FROM 
                %sfaqsection_group fsg
            LEFT JOIN 
                %sfaquser_group fug
            ON
                fug.group_id = fsg.group_id
            WHERE 
                fug.user_id = %d',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId
        );

        $res = $this->config->getDb()->query($select);

        if ($this->config->getDb()->numRows($res) < 1) {
            return [-1];
        }
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['section_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains the right-IDs of all rights
     * the user $userId owns. User-rights and the rights the user
     * owns because of a section membership are taken into account.
     *
     * @param  int $userId
     * @return array<int>
     */
    public function getAllUserRights(int $userId): array
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return [];
        }
        $userRights = $this->getUserRights($userId);
        $groupRights = $this->getUserGroupRights($userId);
        $sectionRights = $this->getUserSectionRights($userId);

        return array_unique(array_merge($userRights, $groupRights, $sectionRights));
    }

    /**
     * Returns an array that contains the IDs of all rights the user
     * $userId owns because of a section membership.
     *
     * @param  int $userId
     * @return array<int>
     */
    public function getUserSectionRights(int $userId): array
    {
        if ($userId < 1 || !is_numeric($userId)) {
            return [];
        }
        $select = sprintf(
            '
            SELECT
                right_id
            FROM 
                %sfaquser_group fug
            LEFT JOIN
                %sfaqgroup_right fgr
            ON
                fgr.group_id = fug.group_id
            WHERE 
                fug.user_id = %d',
            Database::getTablePrefix(),
            Database::getTablePrefix(),
            $userId
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return [];
        }
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            array_push($result, $row['right_id']);
        }
        return $result;
    }

    /**
     * Returns the name of the section $sectionId.
     *
     * @param  int $sectionId
     * @return string
     */
    public function getSectionName(int $sectionId): string
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return '-';
        }
        $select = sprintf(
            '
            SELECT 
                name
            FROM 
                %sfaqsections
            WHERE
                id = %d',
            Database::getTablePrefix(),
            $sectionId
        );
        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return '-';
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row['name'];
    }

    /**
     * Adds a new category $categoryId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $categoryId
     * @param  int $sectionId
     * @return bool
     */
    public function addCategoryToSection(int $categoryId, int $sectionId): bool
    {
        if (!is_numeric($categoryId) || $categoryId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $insert = sprintf(
            '
            INSERT INTO
                %sfaqsection_category
            (category_id, section_id)
                VALUES
            (%s,%s)',
            Database::getTablePrefix(),
            $categoryId,
            $sectionId
        );
        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Removes a category $categoryId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $categoryId
     * @param  int $sectionId
     * @return bool
     */
    public function removeCategoryFromSection(int $categoryId, int $sectionId): bool
    {
        if (!is_numeric($categoryId) || $categoryId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_category
            WHERE 
                category_id = %d
            AND
                section_id = %d',
            Database::getTablePrefix(),
            $categoryId,
            $sectionId
        );
        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns an array that contains the category IDs of all categories
     * of the section $sectionId.
     *
     * @param  int $sectionId
     * @return array<int>
     */
    public function getSectionCategories(int $sectionId): array
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return [];
        }
        $select = sprintf(
            '
            SELECT
                category_id
            FROM
                %sfaqsection_category
            WHERE 
                section_id = %d',
            Database::getTablePrefix(),
            $sectionId
        );
        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['category_id'];
        }
        return $result;
    }

    /**
     * Removes the category $categoryId from all sections.
     * Returns true on success, otherwise false.
     *
     * @param  int $categoryId
     * @return bool
     */
    public function removeCategoryFromAllSections(int $categoryId): bool
    {
        if (!is_numeric($categoryId) || $categoryId < 1) {
            return false;
        }
        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_category
            WHERE 
                category_id = %d',
            Database::getTablePrefix(),
            $categoryId
        );
        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Adds a new news $newsId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $newsId
     * @param  int $sectionId
     * @return bool
     */
    public function addNewsToSection(int $newsId, int $sectionId): bool
    {
        if (!is_numeric($newsId) || $newsId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $insert = sprintf(
            '
            INSERT INTO
                %sfaqsection_news
            (news_id, section_id)
                VALUES
            (%s,%s)',
            Database::getTablePrefix(),
            $newsId,
            $sectionId
        );
        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Removes a news $newsId from the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param  int $newsId
     * @param  int $sectionId
     * @return bool
     */
    public function removeNewsFromSection(int $newsId, int $sectionId): bool
    {
        if (!is_numeric($newsId) || $newsId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_news
            WHERE 
                news_id = %d
            AND
                section_id = %d',
            Database::getTablePrefix(),
            $newsId,
            $sectionId
        );
        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns an array that contains the news IDs of all news
     * of the section $sectionId.
     *
     * @param  int $sectionId
     * @return array<int>
     */
    public function getSectionNews(int $sectionId): array
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return [];
        }
        $select = sprintf(
            '
            SELECT
                news_id
            FROM
                %sfaqsection_news
            WHERE 
                section_id = %d',
            Database::getTablePrefix(),
            $sectionId
        );
        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['news_id'];
        }
        return $result;
    }

    /**
     * Removes the news $newsId from all sections.
     * Returns true on success, otherwise false.
     *
     * @param  int $newsId
     * @return bool
     */
    public function removeNewsFromAllSections(int $newsId): bool
    {
        if (!is_numeric($newsId) || $newsId < 1) {
            return false;
        }
        $delete = sprintf(
            '
            DELETE FROM
                %sfaqsection_news
            WHERE 
                news_id = %d',
            Database::getTablePrefix(),
            $newsId
        );
        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }
}
