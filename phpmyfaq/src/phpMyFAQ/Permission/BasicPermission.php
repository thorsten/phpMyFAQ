<?php

namespace phpMyFAQ\Permission;

/**
 * The basic permission class provides user rights.
 *
 *
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-17
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Db;
use phpMyFAQ\Permission;
use phpMyFAQ\User\CurrentUser;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * BasicPermission.
 *
 * @package phpMyFAQ
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2005-09-17
 */
class BasicPermission extends Permission
{
    /**
     * Default right data stored when a new right is created.
     *
     * @var array
     */
    public $defaultRightData = [
        'name' => 'DEFAULT_RIGHT',
        'description' => 'Short description.',
        'for_users' => true,
        'for_groups' => true,
        'for_sections' => true
    ];

    /**
     * Constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        parent::__construct($config);
    }

    /**
     * Returns true if the user given by user_id has the right
     * specified by right_id, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function checkUserRight($userId, $rightId)
    {
        // check right id
        if ($rightId <= 0) {
            return false;
        }

        // check right
        $select = sprintf('
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = %d AND
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id',
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            $rightId,
            $userId
        );

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) == 1) {
            return true;
        }

        return false;
    }

    /**
     * Returns an array with the IDs of all user-rights the user
     * specified by user_id owns. Group rights are not taken into
     * account.
     *
     * @param int $userId User ID
     *
     * @return array
     */
    public function getUserRights($userId)
    {
        // get user rights
        $select = sprintf('
            SELECT
                fr.right_id AS right_id
            FROM
                %sfaqright fr,
                %sfaquser_right fur,
                %sfaquser fu
            WHERE
                fr.right_id = fur.right_id AND
                fu.user_id  = %d AND
                fu.user_id  = fur.user_id',
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            Db::getTablePrefix(),
            $userId);

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Gives the user a new user-right.
     * Returns true on success, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function grantUserRight($userId, $rightId)
    {
        // is right for users?
        $rightData = $this->getRightData($rightId);

        if (!isset($rightData['for_users'])) {
            return false;
        }

        $insert = sprintf('
            INSERT INTO
                %sfaquser_right
            (user_id, right_id)
                VALUES
            (%d, %d)',
            Db::getTablePrefix(),
            $userId,
            $rightId
        );

        $res = $this->config->getDb()->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Refuses the user a user-right.
     * Returns true on succes, otherwise false.
     *
     * @param int $userId  User ID
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function refuseUserRight($userId, $rightId)
    {
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d AND
                right_id = %d',
            Db::getTablePrefix(),
            $userId,
            $rightId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user given by user_id has the right,
     * otherwise false. Unlike checkUserRight(), right may be a
     * right-ID or a right-name. Another difference is, that also
     * group-rights are taken into account.
     *
     * @param int   $userId User ID
     * @param mixed $right   Right ID or right name
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

        if (!is_numeric($right) and is_string($right)) {
            $right = $this->getRightId($right);
        }

        return $this->checkUserRight($user->getUserId(), $right);
    }

    /**
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the fieldnames.
     *
     * @param int
     *
     * @return array
     */
    public function getRightData($rightId)
    {
        // get right data
        $select = sprintf('
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups,
                for_sections
            FROM
                %sfaqright
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $rightId);

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return [];
        }

        // process right data
        $rightData = $this->config->getDb()->fetchArray($res);
        $rightData['for_users'] = (bool)$rightData['for_users'];
        $rightData['for_groups'] = (bool)$rightData['for_groups'];
        $rightData['for_sections'] = (bool)$rightData['for_sections'];

        return $rightData;
    }

    /**
     * Returns an array that contains the IDs of all user-rights
     * the user owns.
     *
     * @param int $userId User ID
     *
     * @return array
     */
    public function getAllUserRights($userId)
    {
        return $this->getUserRights($userId);
    }

    /**
     * Adds a new right into the database. Returns the ID of the
     * new right. The associative array right_data contains the right 
     * data stored in the rights table. 
     *
     * @param array $rightData Array if rights
     *
     * @return int
     */
    public function addRight(Array $rightData)
    {
        if ($this->getRightId($rightData['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(Db::getTablePrefix().'faqright', 'right_id');
        $rightData = $this->checkRightData($rightData);

        $insert = sprintf("
            INSERT INTO
                %sfaqright
            (right_id, name, description, for_users, for_groups, for_sections)
                VALUES
            (%d, '%s', '%s', %d, %d, %d)",
            Db::getTablePrefix(),
            $nextId,
            $rightData['name'],
            $rightData['description'],
            isset($rightData['for_users']) ? (int)$rightData['for_users'] : 1,
            isset($rightData['for_groups']) ? (int)$rightData['for_groups'] : 1,
            isset($rightData['for_sections']) ? (int)$rightData['for_sections'] : 1
        );

        if (!$this->config->getDb()->query($insert)) {
            return 0;
        }

        return $nextId;
    }

    /**
     * Changes the right data. Returns true on success, otherwise false.
     *
     * @param int   $rightId   Right ID
     * @param array $rightData Array of rights
     *
     * @return bool
     */
    public function changeRight($rightId, Array $rightData)
    {
        $checked_data = $this->checkRightData($rightData);
        $set = '';
        $comma = '';
        foreach ($rightData as $key => $val) {
            $set .= $comma.$key." = '".$checked_data[$key]."'";
            $comma = ",\n                ";
        }

        $update = sprintf('
            UPDATE
                %sfaqright
            SET
                %s
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $set,
            $rightId);

        if (!$this->config->getDb()->query($update)) {
            return false;
        }

        return true;
    }

    /**
     * Renames rights, only used for updates.
     *
     * @param $oldName
     * @param $newName
     * @return bool
     */
    public function renameRight($oldName, $newName)
    {
        $rightId = $this->getRightId($oldName);
        if ($rightId === 0) {
            return false;
        }

        $update = sprintf('
            UPDATE
                %sfaqright
            SET
                name = \'%s\'
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $newName,
            $rightId
        );

        if (!$this->config->getDb()->query($update)) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the right from the database.
     * Returns true on success, otherwise false.
     *
     * @param int $rightId Right ID
     *
     * @return bool
     */
    public function deleteRight($rightId)
    {
        // delete right
        $delete = sprintf('
            DELETE FROM
                %sfaqright
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $rightId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        // delete user-right links
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $rightId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        // delete group-right links
        $delete = sprintf('
            DELETE FROM
                %sfaqgroup_right
            WHERE
                right_id = %d',
            Db::getTablePrefix(),
            $rightId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Returns the right-ID of the right with the name $name.
     *
     * @param string $name Name
     *
     * @return int
     */
    public function getRightId($name)
    {
        // get right id
        $select = sprintf("
            SELECT
                right_id
            FROM
                %sfaqright
            WHERE
                name = '%s'",
            Db::getTablePrefix(),
            $this->config->getDb()->escape($name));

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return 0;
        }
        $row = $this->config->getDb()->fetchArray($res);

        return $row['right_id'];
    }

    /**
     * Returns an array that contains the IDs of all rights stored
     * in the database.
     *
     * @return array
     */
    public function getAllRights()
    {
        $select = sprintf('
            SELECT
                right_id
            FROM
                %sfaqright',
            Db::getTablePrefix());

        $res = $this->config->getDb()->query($select);
        $result = [];
        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * Returns an array that contains all rights stored in the
     * database. Each array element is an associative array with
     * the complete right-data. By passing the optional parameter
     * $order, the order of the array may be specified. Default is
     * $order = 'right_id ASC'.
     *
     * @param string $order Ordering
     *
     * @return array
     */
    public function getAllRightsData($order = 'ASC')
    {
        $select = sprintf('
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups,
                for_sections
            FROM
                %sfaqright
            ORDER BY
                right_id %s',
            Db::getTablePrefix(),
            $order);

        $res = $this->config->getDb()->query($select);
        $result = [];
        $i = 0;

        if ($res) {
            while ($row = $this->config->getDb()->fetchArray($res)) {
                $result[$i] = $row;
                ++$i;
            }
        }

        return $result;
    }

    /**
     * Checks the given associative array $right_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_right_data.
     * Returns the corrected $right_data associative array.
     *
     * @param array $rightData Array of rights
     *
     * @return array
     */
    public function checkRightData(Array $rightData)
    {
        if (!isset($rightData['name']) || !is_string($rightData['name'])) {
            $rightData['name'] = $this->defaultRightData['name'];
        }
        if (!isset($rightData['description']) || !is_string($rightData['description'])) {
            $rightData['description'] = $this->defaultRightData['description'];
        }
        if (!isset($rightData['for_users'])) {
            $rightData['for_users'] = $this->defaultRightData['for_users'];
        }
        if (!isset($rightData['for_groups'])) {
            $rightData['for_groups'] = $this->defaultRightData['for_groups'];
        }
        if (!isset($rightData['for_sections'])) {
            $rightData['for_sections'] = $this->defaultRightData['for_sections'];
        }

        $rightData['for_users'] = (int)$rightData['for_users'];
        $rightData['for_groups'] = (int)$rightData['for_groups'];
        $rightData['for_sections'] = (int)$rightData['for_sections'];

        return $rightData;
    }

    /**
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     *
     * @param int $userId User ID
     *
     * @return bool
     */
    public function refuseAllUserRights($userId)
    {
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d',
            Db::getTablePrefix(),
            $userId);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
}
