<?php
/**
 * The userdata class provides methods to manage user information.
 *
 * PHP version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_User_UserData
 *
 * @category  phpMyFAQ
 * @package   User
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-18
 */
class PMF_User_UserData
{
    /**
     * database object
     *
     * @var PMF_DB_Driver
     */
    private $db = null;

    /**
     * associative array containing user data
     *
     * @var array
     */
    private $data = array();

    /**
     * User-ID
     *
     * @var int
     */
    private $user_id = 0;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->db = PMF_Db::getInstance();
    }

    /**
     * Returns the field $field of the user data. If $field is an
     * array, an associative array will be returned.
     *
     * @param  mixed $field Field(s)
     * @return mixed
     */
    public function get($field)
    {
        $single_return = false;
        if (!is_array($field)) {
            $single_return = true;
            $fields        = $field;
        } else {
            $fields = implode(', ', $field);
        }
        
        $select = sprintf("
            SELECT
                %s
            FROM
                %sfaquserdata
            WHERE
                user_id = %d",
            $fields,
            SQLPREFIX,
            $this->user_id);
        
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            return false;
        }
        $arr = $this->db->fetchArray($res);
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
     * @param  mixed $field Field(s)
     * @param  mixed $value Value(s)
     * @return bool
     */
    public function set($field, $value = null)
    {
        // check input
        if (!is_array($field)) {
            $field = array($field);
        }
        if (!is_array($value)) {
            $value = array($value);
        }
        if (count($field) != count($value)) {
            return false;
        }
        // update data
        for ($i = 0; $i < count($field); $i++) {
            $this->data[$field[$i]] = $value[$i];
        }
        return $this->save();
    }

    /**
     * Loads the user-data from the database and returns an
     * associative array with the fields and values.
     *
     * @param  integer $user_id User ID
     * @return bool
     */
    public function load($user_id)
    {
        $user_id = (int)$user_id;
        if (($user_id <= 0) && ($user_id != -1)) {
            return false;
        }
        
        $this->user_id = $user_id;
        $select        = sprintf("
            SELECT
                last_modified, 
                display_name, 
                email
            FROM
                %sfaquserdata
            WHERE
                user_id = %d",
            SQLPREFIX,
            $this->user_id);
            
        $res = $this->db->query($select);
        if ($this->db->numRows($res) != 1) {
            return false;
        }
        $this->data = $this->db->fetchArray($res);
        return true;
    }

    /**
     * Saves the current user-data into the database.
     * Returns true on success, otherwise false.
     *
     * @return bool
     */
    public function save()
    {
        $update = sprintf("
            UPDATE
                %sfaquserdata
            SET
                last_modified = '%s',
                display_name  = '%s',
                email         = '%s'
            WHERE
                user_id = %d",
            SQLPREFIX,
            date('YmdHis', $_SERVER['REQUEST_TIME']),
            $this->db->escape($this->data['display_name']),
            $this->db->escape($this->data['email']),
            $this->user_id);
            
        $res = $this->db->query($update);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Adds a new user entry for user-data in the database.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id User ID
     * @return bool
     */
    public function add($user_id)
    {
        $user_id = (int) $user_id;
        if (($user_id <= 0) && ($user_id != -1)) {
            return false;
        }
            
        $this->user_id = $user_id;
        $insert        = sprintf("
            INSERT INTO
                %sfaquserdata
            (user_id, last_modified)
                VALUES
            (%d, '%s')",
            SQLPREFIX,
            $this->user_id,
            date('YmdHis', $_SERVER['REQUEST_TIME']));
            
        $res = $this->db->query($insert);
        if (!$res) {
            return false;
        }

        return true;
    }

    /**
     * Deletes the user-data entry for the given user-ID $user_id.
     * Returns true on success, otherwise false.
     *
     * @param  integer $user_id User ID
     * @return bool
     */
    public function delete($user_id)
    {
        $user_id = (int) $user_id;
        if (($user_id <= 0) && ($user_id != -1)) {
            return false;
        }
            
        $this->user_id = $user_id;
        $delete        = sprintf("
            DELETE FROM
                %sfaquserdata
            WHERE
                user_id = %d",
            SQLPREFIX,
            $this->user_id);
        
        $res = $this->db->query($delete);
        if (!$res) {
            return false;
        }
        $this->data = array();
        return true;
    }

}