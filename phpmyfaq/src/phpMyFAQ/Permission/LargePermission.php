<?php

namespace phpMyFAQ\Permission;

/**
 * The large permission class is not yet implemented in phpMyFAQ.
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
        // @todo implement me
        return false;
    }

    /**
     * Adds a new section to the database and returns the ID of the
     * new section. The associative array $sectionData contains the
     * data for the new section.
     *
     * @param array $sectionData
     *
     * @return int
     */
    public function addSection(Array $sectionData)
    {
        // check if section already exists
        if ($this->getSectionId($sectionData['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(Db::getTablePrefix() . 'faqsection', 'section_id');

        // @todo implement me

        return $nextId;
    }

    /**
     * Changes the section data of the given section.
     *
     * @param int $sectionId
     * @param array $sectionData
     * @return bool
     */
    public function changeGroup($sectionId, Array $sectionData)
    {
        // @todo implement me
        return false;
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
        // @todo implement me
        return false;
    }


    /**
     * Returns true if the user given by $userId is a member of
     * the section specified by $sectionId, otherwise false.
     *
     * @param int $sectionId
     * @param int $groupId
     * @return bool
     */
    public function isSectionMember($userId, $sectionId)
    {
        // @todo implement me
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
        // @todo implement me
        return [];
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
        // @todo implement me
        return [];
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
        // @todo implement me
        return false;
    }

    /**
     * Removes a group $groupId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param int $groupId
     * @param int $sectionId
     * @return bool
     */
    public function removeGroupFromSection($groupId, $sectionId)
    {
        // @todo implement me
        return false;
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
        // @todo implement me
        return 0;
    }

    /**
     * Returns an associative array with the section data of the section
     * $groupId.
     *
     * @param int $sectionId
     * @return array
     */
    public function getSectionData($sectionId)
    {
        // @todo implement me
        return [];
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

        // @todo implement me
        return [];
    }

    /**
     * Returns an array with the IDs of all sections stored in the
     * database if no user ID is passed.
     * @param int $userId
     * @return array
     */
    public function getAllSections($userId = 1)
    {
        // @todo implement me
        return [];
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
        // @todo implement me
        return false;
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
     * Returns an array that contains the right-IDs of all rights
     * the user $userId owns. User-rights and the rights the user
     * owns because of a section membership are taken into account.
     *
     * @param int $userId
     * @return array
     */
    public function getAllUserRights($userId)
    {
        // @todo implement me
        return [];
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
        // @todo implement me
        return false;
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
        // @todo implement me
        return [];
    }

    /**
     * Returns the name of the section $sectionId.
     *
     * @param int $sectionId
     * @return string
     */
    public function getSectionName($sectionId)
    {
        // @todo implement me
        return '-';
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
        // @todo implement me
        return false;
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
        // @todo implement me
        return false;
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
        // @todo implement me
        return [];
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
        // @todo implement me
        return false;
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
        // @todo implement me
        return false;
    }

    /**
     * Removes a news $newsId to the section $sectionId.
     * Returns true on success, otherwise false.
     *
     * @param int $newsId
     * @param int $sectionId
     * @return bool
     */
    public function removeNewsFromSection($newsId, $sectionId)
    {
        // @todo implement me
        return false;
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
        // @todo implement me
        return [];
    }

    /**
     * Removes the news $newsId from all sections.
     * Returns true on success, otherwise false.
     *
     * @param int $categoryId
     * @return bool
     */
    public function removeNewsFromAllSections($newsId)
    {
        // @todo implement me
        return false;
    }

}
