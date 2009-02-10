<?php
/**
 * Manages user authentication with databases.
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @since      2005-09-30
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$ 
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

/**
 * PMF_User_AuthDb
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Lars Tiedemann <php@larstiedemann.de>
 * @since      2005-09-30
 * @copyright  2005-2009 phpMyFAQ Team
 * @version    SVN: $Id$ 
 */
class PMF_User_AuthDb extends PMF_User_Auth
{
    /**
     * Database connection
     *
     * @var PMF_DB_Driver
     */
    private $db = null;

    /**
     * Tablename with login credentials
     *
     * @var string
     */
    private $tablename = '';

    /**
     * Login name column
     *
     * @var string
     */
    private $login_column = '';

    /**
     * Password column
     *
     * @var string
     */
    private $password_column = '';

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
        $this->setReadOnly($read_only);
    }

    /**
     * Adds a new user account to the authentication table.
     *
     * Returns true on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @param  string $login Loginname
     * @param  string $pass  Password
     * @return boolean
     */
    public function add($login, $pass)
    {
        if ($this->checkLogin($login) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_ADD . PMF_User_User::ERROR_USER_LOGIN_NOT_UNIQUE;
            return false;
        }
        
        $add = sprintf("
            INSERT INTO
                %s
            (%s, %s)
                VALUES
            ('%s', '%s')",
            $this->getTableName(),
            $this->getLoginColumn(),
            $this->getPasswordColumn(),
            $this->db->escape_string($login),
            $this->db->escape_string($this->enc_container->encrypt($pass)));
            
        $add   = $this->db->query($add);
        $error = $this->db->error();
        
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
     * Changes the password for the account specified by login.
     *
     * Returns true on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @param  string $login Loginname
     * @param  string $pass  Password
     * @return boolean
    */
    public function changePassword($login, $pass)
    {
        $change = sprintf("
            UPDATE
                %s
            SET
                %s = '%s'
            WHERE
                %s = '%s'",
            $this->getTableName(),
            $this->getPasswordColumn(),
            $this->db->escape_string($this->enc_container->encrypt($pass)),
            $this->getLoginColumn(),
            $this->db->escape_string($login));
            
        $change = $this->db->query($change);
        $error  = $this->db->error();
        
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
     * Deletes the user account specified by login.
     *
     * Returns true on success, otherwise false.
     *
     * Error messages are added to the array errors.
     *
     * @param  string $login Loginname
     * @return bool
     */
    public function delete($login)
    {
        $delete = sprintf("
            DELETE FROM
                %s
            WHERE
                %s = '%s'",
            $this->getTableName(),
            $this->getLoginColumn(),
            $this->db->escape_string($login));
            
        $delete = $this->db->query($delete);
        $error  = $this->db->error();
        
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
     * checks the password for the given user account.
     *
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     *
     * @param  string $login Loginname
     * @param  string $pass  Password
     * @return boolean
     */
    public function checkPassword($login, $pass)
    {
        $check = sprintf("
            SELECT
                %s, %s
            FROM
                %s
            WHERE
                %s = '%s'",
            $this->getLoginColumn(),
            $this->getPasswordColumn(),
            $this->getTableName(),
            $this->getLoginColumn(),
            $this->db->escape_string($login));
            
        $check = $this->db->query($check);
        $error = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User_User::ERROR_USER_NOT_FOUND . 'error(): ' . $error;
            return false;
        }
        $num_rows = $this->db->num_rows($check);
        if ($num_rows < 1) {
            $this->errors[] = PMF_User_User::ERROR_USER_NOT_FOUND;
            return false;
        }
        // if login not unique, raise an error, but continue
        if ($num_rows > 1) {
            $this->errors[] = PMF_User_User::ERROR_USER_LOGIN_NOT_UNIQUE;
        }
        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->db->fetch_assoc($check)) {
            if ($user[$this->password_column] == $this->enc_container->encrypt($pass)) {
                return true;
                break;
            }
        }
        $this->errors[] = PMF_User_User::ERROR_USER_INCORRECT_PASSWORD;
        return false;
    }

    /**
     * Establishes a connection to a database server using the given parameters.
     *
     * Selects the database and tries to connect to the specified database server.
     * the database link resource on success, otherwise false.
     *
     * @param  string $table           Table for login credentials
     * @param  string $login_column    Login name column
     * @param  string $password_column Password column
     * @return void
     */
    public function connect($table = '', $login_column = '', $password_column = '')
    {
        $this->db = PMF_Db::getInstance();
        $this->getTableName($table);
        $this->getLoginColumn($login_column);
        $this->getPasswordColumn($password_column);
    }

    /**
     * Checks the number of entries of given login name
     *
     * @param  string $login Loginname
     * @return integer
     */
    public function checkLogin($login)
    {
        $check = sprintf("
            SELECT
                %s
            FROM
                %s
            WHERE
                %s = '%s'",
            $this->getLoginColumn(),
            $this->getTableName(),
            $this->getLoginColumn(),
            $this->db->escape_string($login));
            
        $check = $this->db->query($check);
        $error = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = $error;
            return 0;
        }

        return $this->db->num_rows($check);
    }

    /**
     * sets or returns the table variable.
     *
     * If this method is called without parameter, the object property table is
     * If the property table is not set, an empty string is returned and an
     * message is added to the array errors.
     *
     * If a string table is passed to this method, the object property table
     * be updated. The previous value of the property table is returned.
     *
     * @param  string $table Tablename
     * @return string
     */
    private function getTableName($table = '')
    {
        if ($table != '') {
            $old_table       = $this->tablename;
            $this->tablename = $table;
            return $old_table;
        }
        if (!$this->tablename) {
            $this->errors[]  = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->tablename";
            $this->tablename = '';
        }
        return $this->tablename;
    }

    /**
     * sets or returns the login_column variable.
     *
     * If this method is called without parameter, the object property
     * is returned. If the property login_column is not set, an empty string is
     * and an error message is added to the array errors.
     *
     * If a string login_column is passed to this method, the object property
     * will be updated. The previous value of the property login_column is
     *
     * @param  string $login_column Login name column
     * @return string
     */
    private function getLoginColumn($login_column = '')
    {
        if ($login_column != '') {
            $oldlogin_column    = $this->login_column;
            $this->login_column = $login_column;
            return $oldlogin_column;
        }
        if (!$this->login_column) {
            $this->errors[]     = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->login_column";
            $this->login_column = '';
        }
        return $this->login_column;
    }

    /**
     * sets or returns the password_column variable.
     *
     * If this method is called without parameter, the object property
     * is returned. If the property password_column is not set, an empty string
     * returned and an error message is added to the array errors.
     *
     * If a string password_column is passed to this method, the object property
     * will be updated. The previous value of the property password_column is
     *
     * @param  string $password_column Password column
     * @return string
     */
    private function getPasswordColumn($password_column = '')
    {
        if ($password_column != '') {
            $oldpassword_column    = $this->password_column;
            $this->password_column = $password_column;
            return $oldpassword_column;
        }
        if (!$this->password_column) {
            $this->errors[]        = PMF_User_User::ERROR_UNDEFINED_PARAMETER."PMF_User_AuthDb->password_column";
            $this->password_column = '';
        }
        return (string) $this->password_column;
    }
}
