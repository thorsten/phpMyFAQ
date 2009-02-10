<?php

/**
 * The userdata class provides methods to manage user information.
 *
 * @package     phpMyFAQ
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @since       2005-09-18
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
class PMF_User_UserData
{
    /**
     * database object
     *
     * @var PMF_DB
     */
    private $_db = null;

    /**
     * associative array containing user data
     *
     * @var array
     */
    private $_data = array();

    /**
     * User-ID
     *
     * @var int
     */
    private $_user_id = 0;

    // --- OPERATIONS ---

    /**
     * Constructor. Expects a database object $db.
     *
     * @param  PMF_DB_Driver $db PMF_DB
     * @return void
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     */
    function __construct(PMF_DB_Driver $db)
    {
        $this->_db = $db;
    }

    /**
     * Returns the field $field of the user data. If $field is an
     * array, an associative array will be returned.
     *
     * @param   mixed
     * @return  mixed
     * @access  public
     * @author  Lars Tiedemann <php@larstiedemann.de>
     */
    function get($field)
    {
        // check $field
        $single_return = false;
        if (!is_array($field)) {
            $single_return = true;
            $fields = $field;
        }
        else {
            $fields = implode(', ', $field);
        }
        // get data
        $query = "
            SELECT
                ".$fields."
            FROM
                ".SQLPREFIX."faquserdata
            WHERE
                user_id = ".$this->_user_id;
        $res = $this->_db->query($query);
        if ($this->_db->num_rows($res) != 1) {
            return false;
        }
        $arr = $this->_db->fetch_assoc($res);
        if ($single_return and $field != '*') {
            return $arr[$field];
        }
        return $arr;
    }

    /**
     * Sets the user data given by $field and $value. If $field
     * and $value are arrays, all fields with the corresponding
     * values are updated. Changes are being stored in the database.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param mixed
     * @param mixed
     * @return bool
     */
    function set($field, $value = null)
    {
        // check input
        if (!is_array($field))
            $field = array($field);
        if (!is_array($value))
            $value = array($value);
        if (count($field) != count($value))
            return false;
        // update data
        for ($i = 0; $i < count($field); $i++) {
            $this->_data[$field[$i]] = $value[$i];
        }
        return $this->save();
    }

    /**
     * Loads the user-data from the database and returns an
     * associative array with the fields and values.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function load($user_id)
    {
        // check user-ID
        $user_id = (int) $user_id;
        if (($user_id <= 0) && ($user_id != -1))
            return false;
        $this->_user_id = $user_id;
        // load data
        $res = $this->_db->query("
          SELECT
            last_modified, display_name, email
          FROM
            ".SQLPREFIX."faquserdata
          WHERE
            user_id = ".$this->_user_id
        );
        if ($this->_db->num_rows($res) != 1)
            return false;
        $this->_data = $this->_db->fetch_assoc($res);
        return true;
    }

    /**
     * Saves the current user-data into the database.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return bool
     */
    function save()
    {
        // update data
        $query = sprintf(
                    "UPDATE
                        %sfaquserdata
                    SET
                        last_modified = '%s',
                        display_name  = '%s',
                        email         = '%s'
                    WHERE
                        user_id = %d",
                    SQLPREFIX,
                    date('YmdHis', $_SERVER['REQUEST_TIME']),
                    $this->_data['display_name'],
                    $this->_data['email'],
                    $this->_user_id
                    );
        $res = $this->_db->query($query);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Adds a new user entry for user-data in the database.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function add($user_id)
    {
        // check user-ID
        $user_id = (int) $user_id;
        if (($user_id <= 0) && ($user_id != -1))
            return false;
        $this->_user_id = $user_id;
        // add entry
        $query = sprintf(
                    "INSERT INTO
                        %sfaquserdata
                    (user_id, last_modified)
                    VALUES
                        (%d, '%s')",
                    SQLPREFIX,
                    $this->_user_id,
                    date('YmdHis', $_SERVER['REQUEST_TIME'])
                    );
        $res = $this->_db->query($query);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the user-data entry for the given user-ID $user_id.
     * Returns true on success, otherwise false.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param int
     * @return bool
     */
    function delete($user_id)
    {
        // check user-ID
        $user_id = (int) $user_id;
        if (($user_id <= 0) && ($user_id != -1))
            return false;
        $this->_user_id = $user_id;
        // delete entry
        $res = $this->_db->query("
          DELETE FROM
            ".SQLPREFIX."faquserdata
          WHERE
            user_id = ".$this->_user_id
        );
        if (!$res)
            return false;
        $this->_data = array();
        return true;
    }

} /* end of class PMF_UserData */
