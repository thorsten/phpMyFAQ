<?php
/**
 * Manages user authentication with databases.
 *
 * @package     phpMyFAQ 
 * @author      Lars Tiedemann <php@larstiedemann.de>
 * @access      public
 * @since       2005-09-30
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
class PMF_User_AuthDb extends PMF_User_Auth
{
    /**
    * Short description of attribute db
    *
    * @access private
    * @var mixed
    */
    var $_db = null;

    /**
    * Short description of attribute tablename
    *
    * @access private
    * @var string
    */
    var $_tablename = '';

    /**
    * Short description of attribute login_column
    *
    * @access private
    * @var string
    */
    var $_login_column = '';

    /**
    * Short description of attribute password_column
    *
    * @access private
    * @var string
    */
    var $_password_column = '';



    /**
     * constructor
     *
     * @access  public
     * @author  Lars Tiedemann, <php@larstiedemann.de>
     * @param   string
     * @param   bool
     * @return  void
     */
    function __construct($enctype = 'none', $read_only = false)
    {
        $this->selectEncType($enctype);
        $this->read_only($read_only);
    }

    /**
    * add()
    *
    * Adds a new user account to the authentication table.
    *
    * Returns true on success, otherwise false.
    *
    * Error messages are added to the array errors.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @param    string
    * @return   void
    */
    function add($login, $pass)
    {
        // check if $login already exists
        if ($this->checkLogin($login) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_ADD . PMF_User_User::ERROR_USER_LOGIN_NOT_UNIQUE;
            return false;
        }
        // add user account to authentication table
        $add = "
          INSERT INTO
            ".$this->_tablename()."
          (".$this->_login_column().", ".$this->_password_column().")
            VALUES
          ('".$login."', '".$this->_enc_container->encrypt($pass)."')
        ";
        $add = $this->_db->query($add);
        $error = $this->_db->error();
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_ADD . 'error(): ' . $error;
            return false;
        }
        if (!$add) {
            $this->errors[] = PMF_User_User::ERROR_USER_ADD;
            return false;
        }
        return true;
    }

    /**
    * changePassword()
    *
    * changes the password for the account specified by login.
    *
    * Returns true on success, otherwise false.
    *
    * Error messages are added to the array errors.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @param    string
    * @return   void
    */
    function changePassword($login, $pass)
    {
        $change = "
          UPDATE
            ".$this->_tablename()."
          SET
            ".$this->_password_column()." = '".$this->_enc_container->encrypt($pass)."'
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $change = $this->_db->query($change);
        $error = $this->_db->error();
        if (strlen($error) > 0) {
            $this->errors[] =  PMF_User_User::ERROR_USER_CHANGE . 'error(): ' . $error;
            return false;
        }
        if (!$change) {
            $this->errors[] =  PMF_User_User::ERROR_USER_CHANGE;
            return false;
        }
        return true;
    }

    /**
    * delete()
    *
    * Deletes the user account specified by login.
    *
    * Returns true on success, otherwise false.
    *
    * Error messages are added to the array errors.
    *
    * @access public
    * @author Lars Tiedemann, <php@larstiedemann.de>
    * @param string
    * @return bool
    */
    function delete($login)
    {
        $delete = "
          DELETE FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $delete = $this->_db->query($delete);
        $error = $this->_db->error();
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_DELETE . 'error(): ' . $error;
            return false;
        }
        if (!$delete) {
            $this->errors[] = PMF_User_User::ERROR_USER_DELETE;
            return false;
        }
        return true;
    }

    /**
    * checkPassword()
    *
    * checks the password for the given user account.
    *
    * Returns true if the given password for the user account specified by
    * is correct, otherwise false.
    * Error messages are added to the array errors.
    *
    * @access public
    * @author Lars Tiedemann, <php@larstiedemann.de>
    * @param string
    * @param string
    * @return void
    */
    function checkPassword($login, $pass)
    {
        $check = "
          SELECT
            ".$this->_login_column().",
            ".$this->_password_column()."
          FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column." = '".$login."'
        ";
        $check = $this->_db->query($check);
        $error = $this->_db->error();
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_NOT_FOUND . 'error(): ' . $error;
            return false;
        }
        $num_rows = $this->_db->num_rows($check);
        if ($num_rows < 1) {
            $this->errors[] = PMF_User_User::ERROR_USER_NOT_FOUND;
            return false;
        }
        // if login not unique, raise an error, but continue
        if ($num_rows > 1) {
            $this->errors[] = PMF_User_User::ERROR_USER_LOGIN_NOT_UNIQUE;
        }
        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->_db->fetch_assoc($check)) {
            if ($user[$this->_password_column] == $this->_enc_container->encrypt($pass)) {
                return true;
                break;
            }
        }
        $this->errors[] = PMF_User_User::ERROR_USER_INCORRECT_PASSWORD;
        return false;
    }

    /**
    * connect()
    *
    * Establishes a connection to a MySQL server using the given parameters.
    *
    * Selects the database and tries to connect to the specified MySQL server.
    * the MySQL link resource on success, otherwise false.
    *
    * Error messages are added to the array errors.
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    object
    * @param    string
    * @param    string
    * @param    string
    * @return   void
    */
    function connect($db, $table = '', $login_column = '', $password_column = '')
    {
        $this->_db($db);
        $this->_tablename($table);
        $this->_login_column($login_column);
        $this->_password_column($password_column);
    }

    /**
    * checkLogin()
    *
    * Short description of method checkLogin
    *
    * @access   public
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @return   void
    */
    function checkLogin($login)
    {
        $check = "
          SELECT
            ".$this->_login_column."
          FROM
            ".$this->_tablename()."
          WHERE
            ".$this->_login_column()." = '".$login."'
        ";
        $check = $this->_db->query($check);
        $error = $this->_db->error();
        if (strlen($error) > 0) {
            $this->errors[] = $error;
            return 0;
        }

        return $this->_db->num_rows($check);
    }

    /**
    * _tablename()
    *
    * sets or returns the table variable.
    *
    * If this method is called without parameter, the object property table is
    * If the property table is not set, an empty string is returned and an
    * message is added to the array errors.
    *
    * If a string table is passed to this method, the object property table
    * be updated. The previous value of the property table is returned.
    *
    * @access   private
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @return   string
    */
    function _tablename($table = '')
    {
        if ($table != '') {
            $old_table = $this->_tablename;
            $this->_tablename = $table;
            return $old_table;
        }
        if (!$this->_tablename) {
            $this->errors[] = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->_tablename";
            $this->_tablename = '';
        }
        return $this->_tablename;
    }

    /**
    * _login_column()
    *
    * sets or returns the login_column variable.
    *
    * If this method is called without parameter, the object property
    * is returned. If the property login_column is not set, an empty string is
    * and an error message is added to the array errors.
    *
    * If a string login_column is passed to this method, the object property
    * will be updated. The previous value of the property login_column is
    *
    * @access   private
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @return   string
    */
    function _login_column($login_column = '')
    {
        if ($login_column != '') {
            $old_login_column = $this->_login_column;
            $this->_login_column = $login_column;
            return $old_login_column;
        }
        if (!$this->_login_column) {
            $this->errors[] = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->_login_column";
            $this->_login_column = '';
        }
        return $this->_login_column;
    }

    /**
    * _password_column()
    *
    * sets or returns the password_column variable.
    *
    * If this method is called without parameter, the object property
    * is returned. If the property password_column is not set, an empty string
    * returned and an error message is added to the array errors.
    *
    * If a string password_column is passed to this method, the object property
    * will be updated. The previous value of the property password_column is
    *
    * @access   private
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    string
    * @return   string
    */
    function _password_column($password_column = '')
    {
        if ($password_column != '') {
            $old_password_column = $this->_password_column;
            $this->_password_column = $password_column;
            return $old_password_column;
        }
        if (!$this->_password_column) {
            $this->errors[] = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->_password_column";
            $this->_password_column = '';
        }
        return (string) $this->_password_column;
    }

    /**
    * _db()
    *
    * sets or returns the db variable.
    *
    * If this method is called without parameter, the database object db is
    * If db is invalid, false is returned and an error message is added to the
    * errors.
    *
    * If a valid database object is passed to this method, db will be updated.
    * previous value of db is returned.
    *
    * @access   private
    * @author   Lars Tiedemann, <php@larstiedemann.de>
    * @param    object
    * @return   object
    */
    function _db(PMF_IDB_Driver $db = null)
    {
        if (is_object($db)) {
            $old_db = $this->_db;
            $this->_db = $db;
            return $old_db;
        } else {
            $this->_db = null;
            $this->errors[] = PMF_User_User::ERROR_USER_NO_DB;
            return false;
        }
    }

}
