<?php

/**
 * The userdata class provides methods to manage user information.
 *
 * @author Lars Tiedemann <php@larstiedemann.de>
 * @package PMF
 * @since 2005-09-18
 * @version 0.1
 */

/* user defined includes */

/* user defined constants */

class PMF_UserData
{
    // --- ATTRIBUTES ---

    /**
     * database object
     *
     * @access private
     * @var object
     */
    var $_db = null;

    /**
     * associative array containing user data
     *
     * @access private
     * @var array
     */
    var $_data = array();

    /**
     * user-ID
     *
     * @access private
     * @var int
     */
    var $_user_id = 0;

    // --- OPERATIONS ---

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
                ".PMF_USER_SQLPREFIX."userdata
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
     * Constructor. Expects a database object $db.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @param object
     * @return void
     */
    function PMF_UserData(&$db)
    {
        $this->_db = &$db;
    }

    /**
     * destructor.
     *
     * @access public
     * @author Lars Tiedemann, <php@larstiedemann.de>
     * @return void
     */
    function __destruct()
    {
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
            ".PMF_USER_SQLPREFIX."userdata
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
                        %suserdata
                    SET
                        last_modified = '%s',
                        display_name  = '%s',
                        email         = '%s'
                    WHERE
                        user_id = %d",
                    PMF_USER_SQLPREFIX,
                    date('YmdHis', time()),
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
                        %suserdata
                    (user_id, last_modified)
                    VALUES
                        (%d, '%s')",
                    PMF_USER_SQLPREFIX,
                    $this->_user_id,
                    date('YmdHis', time())
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
            ".PMF_USER_SQLPREFIX."userdata
          WHERE
            user_id = ".$this->_user_id
        );
        if (!$res)
            return false;
        $this->_data = array();
        return true;
    }

} /* end of class PMF_UserData */

?>
