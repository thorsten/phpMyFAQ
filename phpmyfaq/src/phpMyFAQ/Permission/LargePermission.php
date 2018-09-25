<?php

namespace phpMyFAQ\Permission;

/**
 * The large permission class provides section rights for groups and users.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * The large permission class is not yet implemented in phpMyFAQ.
 *
 * @category  phpMyFAQ
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */
class LargePermission extends MediumPermission
{
    /**
     * Default data for new sections.
     *
     * @var array
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
     * @param int $userId
     * @param mixed $right
     *
     * @return bool
     */
    public function checkRight($userId, $right)
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
            $this->checkUserSectionRight($userId, $right) ||
            $this->checkUserGroupRight($userId, $right) ||
            $this->checkUserRight($userId, $right)
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
    public function checkUserSectionRight($userId, $rightId)
    {
        if ($userId < 0 || !is_numeric($userId) || $rightId < 0 || !is_numeric($rightId)) {
            return false;
        }
        $select = sprintf('
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
            DB::getTablePrefix(),
            DB::getTablePrefix(),
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
     * @param array $sectionData Array of section data
     *
     * @return int
     */
    public function addSection(Array $sectionData)
    {
        // check if section already exists
        if ($this->getSectionId($sectionData['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(Db::getTablePrefix().'faqsections', 'id');
        $sectionData = $this->checkSectionData($sectionData);
        $insert = sprintf("
            INSERT INTO
                %sfaqsections
            (id, name, description)
                VALUES
            (%d, '%s', '%s')",
            Db::getTablePrefix(),
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
     * Changes the section data of the given section.
     *
     * @param int $sectionId
     * @param array $sectionData
     * @return bool
     */
    public function changeSection($sectionId, Array $sectionData)
    {
        $checkedData = $this->checkSectionData($sectionData);
        $set = '';
        $comma = '';

        foreach ($sectionData as $key => $val) {
            $set .= $comma . $key . " = '" . $this->config->getDb()->escape($checkedData[$key]) . "'";
            $comma = ",\n                ";
        }

        $update = sprintf('
            UPDATE
                %sfaqsections
            SET
                %s
            WHERE
                id = %d',
            Db::getTablePrefix(),
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
     * Checks the given associative array $sectionData. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->defaultSectionData.
     * Returns the corrected $sectionData associative array.
     *
     * @param array $sectionData
     * @return array
     */
    public function checkSectionData(Array $sectionData)
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
     * Removes the section given by $sectionId from the database.
     * Returns true on success, otherwise false.
     *
     * @param int $sectionId
     * @return bool
     */
    public function deleteSection($sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return false;
        }

        $delete = sprintf('
            DELETE FROM
                %sfaqsections
            WHERE
                id = %d',
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf('
            DELETE FROM
                %sfaqsection_group
            WHERE
                section_id = %d',
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $delete = sprintf('
            DELETE FROM
                %sfaqsection_news
            WHERE
                section_id = %d',
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user given by $userId is a member of
     * the section specified by $sectionId, otherwise false.
     *
     * @param int $userId
     * @param int $sectionId
     * @return bool
     */
    public function isSectionMember($userId, $sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId) || $userId <= 0 || !is_numeric($userId)) {
            return false;
        }

        $select = sprintf('
            SELECT 
                fsg.user_id
            FROM
                %sfaqsection_group fsg
            LEFT JOIN 
                %sfaquser_group fug
            ON 
                fug.group_id = fsg.group_id
            WHERE 
                fug.user_id = %d
            AND fsg.section_id = %d
            ',
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            $sectionId,
            $userId
        );

        $res = $this->config->getDb()->query($select);

        if ($this->config->getDb()->numRows($res) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array that contains the user IDs of all members
     * of the section $sectionId.
     *
     * @param int $sectionId
     * @return array
     */
    public function getSectionMembers($sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return [];
        }

        $select = sprintf('
            SELECT 
                fsg.user_id
            FROM
                %sfaqsection_group fsg
            LEFT JOIN 
                %sfaquser_group fug
            ON 
                fug.group_id = fsg.group_id
            WHERE 
                fsg.section_id = %d
            ',
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($select);

        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['user_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains the group IDs of all groups
     * of the section $sectionId.
     *
     * @param int $sectionId
     * @return array
     */
    public function getSectionGroups($sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return [];
        }

        $select = sprintf('
            SELECT 
                %sfaqsection_group.group_id
            FROM
                %sfaqsection_group
            WHERE 
                %sfaqsection_group.section_id = %d
            ',
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            Db::getTablePrefix(),
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
     * @param int $groupId
     * @param int $sectionId
     * @return bool
     */
    public function addGroupToSection($groupId, $sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId) | $groupId <= 0 || !is_numeric($groupId)) {
            return false;
        }

        $select = sprintf('
            SELECT 
                group_id
            FROM
                %sfaqsection_group
            WHERE 
                section_id = %d
            AND 
                group_id = %d
            ',
            Db::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($select);

        if ($this->config->getDb()->numRows($res) > 0) {
            return false;
        }

        $insert = sprintf('
            INSERT INTO
                %sfaqsection_group
            (section_id, group_id)
               VALUES
            (%d, %d)',
            Db::getTablePrefix(),
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
     * @param int $sectionId
     * @return bool
     */
    public function removeAllGroupsFromSection($sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId)) {
            return false;
        }

        $delete =  sprintf('
            DELETE FROM
                %sfaqsection_group
            WHERE
                section_id = %d',
            DB::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Removes a group $groupId from the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId
     * @param int $sectionId
     * @return bool
     */
    public function removeGroupFromSection($groupId, $sectionId)
    {
        if ($sectionId <= 0 || !is_numeric($sectionId) | $groupId <= 0 || !is_numeric($groupId)) {
            return false;
        }

        $delete = sprintf('
            DELETE FROM
                %sfaqsection_group
            WHERE
                group_id = %d
            AND
                section_id = %d',
            Db::getTablePrefix(),
            $sectionId,
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns the ID of the section that has the name $name. Returns
     * 0 if the section name cannot be found.
     *
     * @param string $name
     * @return int
     */
    public function getSectionId($name)
    {
        $select = sprintf('
            SELECT 
                    id
            FROM 
                %sfaqsections
            WHERE 
                name = %s',
            Db::getTablePrefix(),
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
     * Returns an associative array with the section data of the section
     * $sectionId.
     *
     * @param int $sectionId
     * @return array
     */
    public function getSectionData($sectionId)
    {
        $select = sprintf('
            SELECT 
                    *
            FROM 
                %sfaqsections
            WHERE 
                id = %d',
            Db::getTablePrefix(),
            $sectionId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return 0;
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row;
    }

    /**
     * Returns an array with the IDs of all sections stored in the
     * database if no user ID is passed.
     * @param int $userId
     * @return array
     */
    public function getAllSections($userId = 1)
    {
        if ($userId != 1) {
            return $this->getUserSections($userId);
        }

        $select = sprintf('SELECT * FROM %sfaqsections', Db::getTablePrefix());

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
     * @param int $userId
     * @return array
     */
    public function getUserSections($userId)
    {
        if ($userId <= 0 || !is_numeric($userId)) {
            return [-1];
        }

        $select = sprintf('
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
            Db::getTablePrefix(),
            Db::getTablePrefix(),
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
     * @param int $userId
     * @return array
     */
    public function getAllUserRights($userId)
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
     * Removes the group $groupId from all sections.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId
     * @return bool
     */
    public function removeGroupFromAllSections($groupId)
    {
        if ($groupId < 1 || !is_numeric($groupId)) {
            return false;
        }

        $delete = sprintf('
            DELETE FROM
                %sfaqsection_group
            WHERE 
                group_id = %s',
            DB::getTablePrefix(),
            $groupId
        );

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

    /**
     * Returns an array that contains the IDs of all rights the user
     * $userId owns because of a section membership.
     *
     * @param int $userId
     * @return array
     */
    public function getUserSectionRights($userId)
    {
        if ($userId < 1 || !is_numeric($userId)) {
            return [];
        }
        $select = sprintf('
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
            DB::getTablePrefix(),
            DB::getTablePrefix(),
            $userId
        );

        $res = $this->config->getDb()->query($select);
        if (!$res) {
            return [];
        }
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * Returns the name of the section $sectionId.
     *
     * @param int $sectionId
     * @return string
     */
    public function getSectionName($sectionId)
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return '-';
        }
        $select = sprintf('
            SELECT 
                name
            FROM 
                %sfaqsections
            WHERE
                id = %d',
            DB::getTablePrefix(),
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
     * @param int $categoryId
     * @param int $sectionId
     * @return bool
     */
    public function addCategoryToSection($categoryId, $sectionId)
    {
        if (!is_numeric($categoryId) || $categoryId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $insert = sprintf('
            INSERT INTO
                %sfaqsection_category
            (category_id, section_id)
                VALUES
            (%s,%s)',
            DB::getTablePrefix(),
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
     * @param int $categoryId
     * @param int $sectionId
     * @return bool
     */
    public function removeCategoryFromSection($categoryId, $sectionId)
    {
        if (!is_numeric($categoryId) || $categoryId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $delete = sprintf('
            DELETE FROM
                %sfaqsection_category
            WHERE 
                category_id = %d
            AND
                section_id = %d',
            DB::getTablePrefix(),
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
     * @param int $sectionId
     * @return array
     */
    public function getSectionCategories($sectionId)
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return [];
        }
        $select = sprintf('
            SELECT
                category_id
            FROM
                %sfaqsection_category
            WHERE 
                section_id = %d',
            DB::getTablePrefix(),
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
     * @param int $categoryId
     * @return bool
     */
    public function removeCategoryFromAllSections($categoryId)
    {
        if (!is_numeric($categoryId) || $categoryId < 1) {
            return false;
        }
        $delete = sprintf('
            DELETE FROM
                %sfaqsection_category
            WHERE 
                category_id = %d',
            DB::getTablePrefix(),
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
     * @param int $newsId
     * @param int $sectionId
     * @return bool
     */
    public function addNewsToSection($newsId, $sectionId)
    {
        if (!is_numeric($newsId) || $newsId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $insert = sprintf('
            INSERT INTO
                %sfaqsection_news
            (news_id, section_id)
                VALUES
            (%s,%s)',
            DB::getTablePrefix(),
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
     * @param int $newsId
     * @param int $sectionId
     * @return bool
     */
    public function removeNewsFromSection($newsId, $sectionId)
    {
        if (!is_numeric($newsId) || $newsId < 1 || !is_numeric($sectionId) || $sectionId < 1) {
            return false;
        }
        $delete = sprintf('
            DELETE FROM
                %sfaqsection_news
            WHERE 
                news_id = %d
            AND
                section_id = %d',
            DB::getTablePrefix(),
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
     * @param int $sectionId
     * @return array
     */
    public function getSectionNews($sectionId)
    {
        if (!is_numeric($sectionId) || $sectionId < 1) {
            return [];
        }
        $select = sprintf('
            SELECT
                news_id
            FROM
                %sfaqsection_news
            WHERE 
                section_id = %d',
            DB::getTablePrefix(),
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
     * @param int $newsId
     * @return bool
     */
    public function removeNewsFromAllSections($newsId)
    {
        if (!is_numeric($newsId) || $newsId < 1) {
            return false;
        }
        $delete = sprintf('
            DELETE FROM
                %sfaqsection_news
            WHERE 
                news_id = %d',
            DB::getTablePrefix(),
            $newsId
        );
        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }
        return true;
    }

}
