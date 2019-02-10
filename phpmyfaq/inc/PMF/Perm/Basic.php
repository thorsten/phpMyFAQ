<?php

/**
 * The basic permission class provides user rights.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Perm_Basic.
 *
 * @category  phpMyFAQ 
 *
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2005-09-17
 */
class PMF_Perm_Basic extends PMF_Perm
{
    // --- ATTRIBUTES ---

    /**
     * default_right_data.
     *
     * default right data stored when a new right is created.
     *
     * @var array
     */
    public $default_right_data = array(
        'name' => 'DEFAULT_RIGHT',
        'description' => 'Short description.',
        'for_users' => true,
        'for_groups' => true,
    );

    /**
     * Constructor.
     *
     * @param PMF_Configuration $config
     *
     * @return PMF_Perm_Basic
     */
    public function __construct(PMF_Configuration $config)
    {
        parent::__construct($config);
    }

    // --- OPERATIONS ---

    /**
     * Returns true if the user given by user_id has the right
     * specified by right_id, otherwise false.
     *
     * @param int $user_id  User ID
     * @param int $right_id Right ID
     *
     * @return bool
     */
    public function checkUserRight($user_id, $right_id)
    {
        // check right id
        if ($right_id <= 0) {
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
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $right_id,
            $user_id);

        $res = $this->config->getDb()->query($select);
        // return result
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
     * @param int $user_id User ID
     *
     * @return array
     */
    public function getUserRights($user_id)
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
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            PMF_Db::getTablePrefix(),
            $user_id);

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
     * @param int $user_id  User ID
     * @param int $right_id Right ID
     *
     * @return bool
     */
    public function grantUserRight($user_id, $right_id)
    {
        // is right for users?
        $right_data = $this->getRightData($right_id);

        if (!$right_data['for_users']) {
            return false;
        }

        $insert = sprintf('
            INSERT INTO
                %sfaquser_right
            (user_id, right_id)
                VALUES
            (%d, %d)',
            PMF_Db::getTablePrefix(),
            $user_id,
            $right_id);

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
     * @param int $user_id  User ID
     * @param int $right_id Right ID
     *
     * @return bool
     */
    public function refuseUserRight($user_id, $right_id)
    {
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d AND
                right_id = %d',
            PMF_Db::getTablePrefix(),
            $user_id,
            $right_id);

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
     * @param int   $user_id User ID
     * @param mixed $right   Right ID or right name
     *
     * @return bool
     */
    public function checkRight($user_id, $right)
    {
        if (!is_numeric($right) and is_string($right)) {
            $right = $this->getRightId($right);
        }

        return $this->checkUserRight($user_id, $right);
    }

    /**
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the fieldnames.
     *
     * @author Lars Tiedemann, <php@larstiedemann.de>
     *
     * @param int
     *
     * @return array
     */
    public function getRightData($right_id)
    {
        // get right data
        $select = sprintf('
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups
            FROM
                %sfaqright
            WHERE
                right_id = %d',
            PMF_Db::getTablePrefix(),
            $right_id);

        $res = $this->config->getDb()->query($select);
        if ($this->config->getDb()->numRows($res) != 1) {
            return false;
        }

        // process right data
        $right_data = $this->config->getDb()->fetchArray($res);
        $right_data['for_users'] = (bool) $right_data['for_users'];
        $right_data['for_groups'] = (bool) $right_data['for_groups'];

        return $right_data;
    }

    /**
     * Returns an array that contains the IDs of all user-rights
     * the user owns.
     *
     * @param int $user_id User ID
     *
     * @return array
     */
    public function getAllUserRights($user_id)
    {
        return $this->getUserRights($user_id);
    }

    /**
     * Dummy function; this function is only relevant when the
     * permission mode is set to Medium.
     *
     * @param int $user_id User ID
     *
     * @return bool
     */
    public function autoJoin($user_id)
    {
        return true;
    }

    /**
     * Adds a new right into the database. Returns the ID of the
     * new right. The associative array right_data contains the right 
     * data stored in the rights table. 
     *
     * @param array $right_data Array if rights
     *
     * @return int
     */
    public function addRight(Array $right_data)
    {
        if ($this->getRightId($right_data['name']) > 0) {
            return 0;
        }

        $nextId = $this->config->getDb()->nextId(PMF_Db::getTablePrefix().'faqright', 'right_id');
        $rightData = $this->checkRightData($right_data);

        $insert = sprintf("
            INSERT INTO
                %sfaqright
            (right_id, name, description, for_users, for_groups)
                VALUES
            (%d, '%s', '%s', %d, %d)",
            PMF_Db::getTablePrefix(),
            $nextId,
            $rightData['name'],
            $rightData['description'],
            isset($rightData['for_users']) ? (int) $rightData['for_users'] : 1,
            isset($rightData['for_groups']) ? (int) $rightData['for_groups'] : 1
        );

        if (!$this->config->getDb()->query($insert)) {
            return 0;
        }

        return $nextId;
    }

    /**
     * Changes the right data. Returns true on success, otherwise false.
     *
     * @param int   $right_id   Right ID
     * @param array $right_data Array of rights
     *
     * @return bool
     */
    public function changeRight($right_id, Array $right_data)
    {
        $checked_data = $this->checkRightData($right_data);
        $set = '';
        $comma = '';
        foreach ($right_data as $key => $val) {
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
            PMF_Db::getTablePrefix(),
            $set,
            $right_id);

        $res = $this->config->getDb()->query($update);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the right from the database.
     * Returns true on success, otherwise false.
     *
     * @param int $right_id Right ID
     *
     * @return bool
     */
    public function deleteRight($right_id)
    {
        // delete right
        $delete = sprintf('
            DELETE FROM
                %sfaqright
            WHERE
                right_id = %d',
            PMF_Db::getTablePrefix(),
            $right_id);

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
            PMF_Db::getTablePrefix(),
            $right_id);

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
            PMF_Db::getTablePrefix(),
            $right_id);

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
            PMF_Db::getTablePrefix(),
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
            PMF_Db::getTablePrefix());

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
                for_groups
            FROM
                %sfaqright
            ORDER BY
                right_id %s',
            PMF_Db::getTablePrefix(),
            $order);

        $res = $this->config->getDb()->query($select);
        $result = [];
        $i = 0;

        while ($row = $this->config->getDb()->fetchArray($res)) {
            $result[$i] = $row;
            ++$i;
        }

        return $result;
    }

    /**
     * Checks the given associative array $right_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_right_data.
     * Returns the corrected $right_data associative array.
     *
     * @param array $right_data Array of rights
     *
     * @return array
     */
    public function checkRightData(Array $right_data)
    {
        if (!isset($right_data['name']) || !is_string($right_data['name'])) {
            $right_data['name'] = $this->default_right_data['name'];
        }
        if (!isset($right_data['description']) || !is_string($right_data['description'])) {
            $right_data['description'] = $this->default_right_data['description'];
        }
        if (!isset($right_data['for_users'])) {
            $right_data['for_users'] = $this->default_right_data['for_users'];
        }
        if (!isset($right_data['for_groups'])) {
            $right_data['for_groups'] = $this->default_right_data['for_groups'];
        }

        $right_data['for_users'] = (int) $right_data['for_users'];
        $right_data['for_groups'] = (int) $right_data['for_groups'];

        return $right_data;
    }

    /**
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     *
     * @param int $user_id User ID
     *
     * @return bool
     */
    public function refuseAllUserRights($user_id)
    {
        $delete = sprintf('
            DELETE FROM
                %sfaquser_right
            WHERE
                user_id  = %d',
            PMF_Db::getTablePrefix(),
            $user_id);

        $res = $this->config->getDb()->query($delete);
        if (!$res) {
            return false;
        }

        return true;
    }
}
