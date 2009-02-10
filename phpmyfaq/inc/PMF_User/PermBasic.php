<?php
/**
 * The basic permission class provides user rights.
 *
 * @package     phpMyFAQ 
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-17
 * @copyright   (c) 2005-2009 phpMyFAQ Team
 * @version     SVN: $Id$ 
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 */
class PMF_User_PermBasic extends PMF_User_Perm
{
    // --- ATTRIBUTES ---

    /**
     * default_right_data
     *
     * default right data stored when a new right is created.
     *
     * @access public
     * @var array
     */
    public $default_right_data = array(
        'name'          => 'DEFAULT_RIGHT',
        'description'   => 'Short description.',
        'for_users'     => true,
        'for_groups'    => true
    );

    // --- OPERATIONS ---

    /**
     * checkUserRight
     *
     * Returns true if the user given by user_id has the right
     * specified by right_id, otherwise false.
     *
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    public function checkUserRight($user_id, $right_id)
    {
        // check right id
        if ($right_id <= 0)
            return false;
        // check right
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."faqright.right_id AS right_id
            FROM
                ".SQLPREFIX."faqright,
                ".SQLPREFIX."faquser_right,
                ".SQLPREFIX."faquser
            WHERE
                ".SQLPREFIX."faqright.right_id = ".$right_id." AND
                ".SQLPREFIX."faqright.right_id = ".SQLPREFIX."faquser_right.right_id AND
                ".SQLPREFIX."faquser.user_id   = ".$user_id." AND
                ".SQLPREFIX."faquser.user_id   = ".SQLPREFIX."faquser_right.user_id
        ");
        // return result
        if ($this->_db->num_rows($res) == 1)
            return true;
        return false;
    }

    /**
     * getUserRights
     *
     * Returns an array with the IDs of all user-rights the user
     * specified by user_id owns. Group rights are not taken into
     * account.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getUserRights($user_id)
    {
        // get user rights
        $res = $this->_db->query("
            SELECT
                ".SQLPREFIX."faqright.right_id AS right_id
            FROM
                ".SQLPREFIX."faqright,
                ".SQLPREFIX."faquser_right,
                ".SQLPREFIX."faquser
            WHERE
                ".SQLPREFIX."faqright.right_id = ".SQLPREFIX."faquser_right.right_id AND
                ".SQLPREFIX."faquser.user_id   = ".$user_id." AND
                ".SQLPREFIX."faquser.user_id   = ".SQLPREFIX."faquser_right.user_id
        ");
        // return result
        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[] = $row['right_id'];
        }
        return $result;
    }

    /**
     * grantUserRight
     *
     * Gives the user a new user-right.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function grantUserRight($user_id, $right_id)
    {
        // is right for users?
        $right_data = $this->getRightData($right_id);
        if (!$right_data['for_users'])
            return false;
        // grant user right
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."faquser_right
            (user_id, right_id)
                VALUES
            (".$user_id.", ".$right_id.")"
        );
        if (!$res)
            return false;
        return true;
    }

    /**
     * refuseUserRight
     *
     * Refuses the user a user-right.
     * Returns true on succes, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param int
     * @return bool
     */
    function refuseUserRight($user_id, $right_id)
    {
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faquser_right
            WHERE
                user_id  = ".$user_id." AND
                right_id = ".$right_id
        );
        if (!$res)
            return false;
        return true;
    }

    /**
     * checkRight
     *
     * Returns true if the user given by user_id has the right,
     * otherwise false. Unlike checkUserRight(), right may be a
     * right-ID or a right-name. Another difference is, that also
     * group-rights are taken into account.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param mixed
     * @return bool
     */
    function checkRight($user_id, $right)
    {
        // get right id
        if (!is_numeric($right) and is_string($right))
            $right = $this->getRightId($right);
        // check user right
        return $this->checkUserRight($user_id, $right);
    }

    /**
     * getRightData
     *
     * Returns an associative array with all data stored for in the
     * database for the specified right. The keys of the returned
     * array are the fieldnames.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    public function getRightData($right_id)
    {
        // get right data
        $res = $this->_db->query("
            SELECT
                right_id,
                name,
                description,
                for_users,
                for_groups
            FROM
                ".SQLPREFIX."faqright
            WHERE
                right_id = ".$right_id
        );
        
        if ($this->_db->num_rows($res) != 1) {
            return false;
        }
        
        // process right data
        $right_data               = $this->_db->fetch_assoc($res);
        $right_data['for_users']  = $this->int_to_bool($right_data['for_users']);
        $right_data['for_groups'] = $this->int_to_bool($right_data['for_groups']);
        return $right_data;
    }

    /**
     * getAllUserRights
     *
     * Returns an array that contains the IDs of all user-rights
     * the user owns.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return array
     */
    function getAllUserRights($user_id)
    {
        return $this->getUserRights($user_id);
    }

    /**
     * addRight
     *
     * Adds a new right into the database. Returns the ID of the
     * new right.
     * The associative array right_data contains the right data
     * stored in the rights table. The associative array
     * context_data is only for use with PMF_PermLarge and may be
     * omitted.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @param array
     * @return int
     */
    function addRight($right_data, $context_data = array())
    {
        // check if right already exists
        if ($this->getRightId($right_data['name']) > 0)
            return 0;
        // get next id
        $next_id = $this->_db->nextID(SQLPREFIX."faqright", "right_id");
        // check right data input
        $right_data = $this->checkRightData($right_data);
        // insert right
        $res = $this->_db->query("
            INSERT INTO
                ".SQLPREFIX."faqright
            (right_id, name, description, for_users, for_groups)
                VALUES
            (".$next_id.", '".$right_data['name']."', '".$right_data['description']."', ".$this->bool_to_int($right_data['for_users']).", ".$this->bool_to_int($right_data['for_groups']).")"
        );
        if (!$res)
            return 0;
        // insert context data
        if (count($context_data) > 0) {
            $res = $this->_db->query("
                INSERT INTO
                    ".SQLPREFIX."faqrightcontext
                (right_id, context, context_id)
                  VALUES
                (".$next_id.", '".$context_data['context']."', ".$context_data['context_id'].")"
            );
            if (!$res)
                return 0;
        }
        return $next_id;
    }

    /**
     * changeRight
     *
     * Changes the right data.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @param array
     * @param array
     * @return bool
     */
    function changeRight($right_id, $right_data, $context_data = array())
    {
        // check input
        $checked_data = $this->checkRightData($right_data);
        // create update SET
        $set = "";
        $comma = "";
        foreach ($right_data as $key => $val) {
            $set .= $comma.$key." = '".$checked_data[$key]."'";
            $comma = ",\n                ";
        }
        // update right
        $res = $this->_db->query("
            UPDATE
                ".SQLPREFIX."faqright
            SET
                ".$set."
            WHERE
                right_id = ".$right_id
        );
        if (!$res)
            return false;
        // change right context
        if (count($context_data) > 0) {
            $res = $this->_db->query("
                UPDATE
                    ".SQLPREFIX."faqrightcontext
                SET
                    context    = '".$context_data['context']."',
                    context_id = ".$context_data['context_id']."
                WHERE
                    right_id = ".$right_id
            );
            if (!$res)
                return false;
        }
        return true;
    }

    /**
     * deleteRight
     *
     * Deletes the right from the database.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function deleteRight($right_id)
    {
        // delete right
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faqright
            WHERE
                right_id = ".$right_id."
        ");
        if (!$res)
            return false;
        // delete user-right links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faquser_right
            WHERE
                right_id = ".$right_id."
        ");
        if (!$res)
            return false;
        // delete group-right links
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faqgroup_right
            WHERE
                right_id = ".$right_id."
        ");
        if (!$res)
            return false;
        // delete right context
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faqrightcontext
            WHERE
                right_id = ".$right_id."
        ");
        if (!$res)
            return false;
        return true;
    }

    /**
     * getRightId
     *
     * Returns the right-ID of the right with the name $name.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return int
     */
    function getRightId($name)
    {
        // get right id
        $res = $this->_db->query("
            SELECT
                right_id
            FROM
                ".SQLPREFIX."faqright
            WHERE
                name = '".$name."'
        ");
        // return result
        if ($this->_db->num_rows($res) != 1)
            return 0;
        $row = $this->_db->fetch_assoc($res);
        return $row['right_id'];
    }

    /**
     * getAllRights
     *
     * Returns an array that contains the IDs of all rights stored
     * in the database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return array
     */
    function getAllRights()
    {
        $query = sprintf(
                    "SELECT
                        right_id
                    FROM
                        %sfaqright",
                    SQLPREFIX
                    );
        $res = $this->_db->query($query);

        $result = array();
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[] = $row['right_id'];
        }

        return $result;
    }

    /**
     * getAllRightsData
     *
     * Returns an array that contains all rights stored in the
     * database. Each array element is an associative array with
     * the complete right-data. By passing the optional parameter
     * $order, the order of the array may be specified. Default is
     * $order = 'right_id ASC'.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param string
     * @return array
     */
    function getAllRightsData($order = 'right_id ASC')
    {
        $query = sprintf(
                    "SELECT
                        right_id,
                        name,
                        description,
                        for_users,
                        for_groups
                    FROM
                        %sfaqright
                    ORDER BY
                        %s",
                    SQLPREFIX,
                    $order
                    );
        $res = $this->_db->query($query);

        $result = array();
        $i = 0;
        while ($row = $this->_db->fetch_assoc($res)) {
            $result[$i] = $row;
            $i++;
        }

        return $result;
    }

    /**
     * checkRightData
     *
     * Checks the given associative array $right_data. If a
     * parameter is incorrect or is missing, it will be replaced
     * by the default values in $this->default_right_data.
     * Returns the corrected $right_data associative array.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param array
     * @return array
     */
    function checkRightData($right_data)
    {
        if (!isset($right_data['name']) or !is_string($right_data['name']))
            $right_data['name'] = $this->default_right_data['name'];
        if (!isset($right_data['description']) or !is_string($right_data['description']))
            $right_data['description'] = $this->default_right_data['description'];
        if (!isset($right_data['for_users']))
            $right_data['for_users'] = $this->default_right_data['for_users'];
        if (!isset($right_data['for_groups']))
            $right_data['for_groups'] = $this->default_right_data['for_groups'];
        $right_data['for_users'] = $this->bool_to_int($right_data['for_users']);
        $right_data['for_groups'] = $this->bool_to_int($right_data['for_groups']);
        return $right_data;
    }

    /**
     * refuseAllUserRights
     *
     * Refuses all user rights.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function refuseAllUserRights($user_id)
    {
        $res = $this->_db->query("
            DELETE FROM
                ".SQLPREFIX."faquser_right
            WHERE
                user_id  = ".$user_id
        );
        if (!$res)
            return false;
        return true;
    }
}