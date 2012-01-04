<?php
/**
 * Manages user authentication with databases.
 *
 * PHP Version 5.2
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
 * 
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Db
 *
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Lars Tiedemann <php@larstiedemann.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2005-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2005-09-30
 */
class PMF_Auth_Db extends PMF_Auth implements PMF_Auth_Driver
{
    /**
     * Database connection
     *
     * @var PMF_DB_Driver
     */
    private $db = null;

    /**
     * Constructor
     *
     * @param  string  $enctype   Type of encoding
     * @param  boolean $read_only Readonly?
     * @return void
     */
    function __construct($enctype = 'none', $read_only = false)
    {
        parent::__construct($enctype, $read_only);
        
        $this->db = PMF_Db::getInstance();
    }

    /**
     * Adds a new user account to the faquserlogin table. Returns true on
     * success, otherwise false. Error messages are added to the array errors.
     *
     * @param string $login    Loginname
     * @param string $password Password
     *
     * @return boolean
     */
    public function add($login, $pass)
    {
        if ($this->checkLogin($login) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_ADD . PMF_User::ERROR_USER_LOGIN_NOT_UNIQUE;
            return false;
        }
        
        $add = sprintf("
            INSERT INTO
                %sfaquserlogin
            (login, pass)
                VALUES
            ('%s', '%s')",
            SQLPREFIX,
            $this->db->escape($login),
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($pass)));
            
        $add   = $this->db->query($add);
        $error = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_ADD . 'error(): ' . $error;
            return false;
        }
        if (!$add) {
            $this->errors[] = PMF_User::ERROR_USER_ADD;
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
                %sfaquserlogin
            SET
                pass = '%s'
            WHERE
                login = '%s'",
            SQLPREFIX,
            $this->db->escape($this->encContainer->setSalt($login)->encrypt($pass)),
            $this->db->escape($login));
            
        $change = $this->db->query($change);
        $error  = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] =  PMF_User::ERROR_USER_CHANGE . 'error(): ' . $error;
            return false;
        }
        if (!$change) {
            $this->errors[] =  PMF_User::ERROR_USER_CHANGE;
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
                %sfaquserlogin
            WHERE
                login = '%s'",
            SQLPREFIX,
            $this->db->escape($login));
            
        $delete = $this->db->query($delete);
        $error  = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_DELETE . 'error(): ' . $error;
            return false;
        }
        if (!$delete) {
            $this->errors[] = PMF_User::ERROR_USER_DELETE;
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
     * @param  string $login        Loginname
     * @param  string $password     Password
     * @param  array  $optionslData Optional data
     * @return boolean
     */
    public function checkPassword($login, $password, Array $optionalData = null)
    {
        $check = sprintf("
            SELECT
                login, pass
            FROM
                %sfaquserlogin
            WHERE
                login = '%s'",
            SQLPREFIX,
            $this->db->escape($login));
            
        $check = $this->db->query($check);
        $error = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = PMF_User::ERROR_USER_NOT_FOUND . 'error(): ' . $error;
            return false;
        }

        $numRows = $this->db->numRows($check);
        if ($numRows < 1) {
            $this->errors[] = PMF_User::ERROR_USER_NOT_FOUND;
            return false;
        }

        // if login not unique, raise an error, but continue
        if ($numRows > 1) {
            $this->errors[] = PMF_User::ERROR_USER_LOGIN_NOT_UNIQUE;
        }

        // if multiple accounts are ok, just 1 valid required
        while ($user = $this->db->fetchArray($check)) {

            // Check password against old one
            if (PMF_Configuration::getInstance()->get('security.forcePasswordUpdate')) {
                if ($this->checkEncryptedString($user['pass']) &&
                    $this->encContainer->setSalt($user['login'])->encrypt($password) !== $user['pass']) {
                    $this->changePassword($login, $password);
                    return $this->checkPassword($login, $password);
                }
            }

            if ($user['pass'] === $this->encContainer->setSalt($user['login'])->encrypt($password)) {
                return true;
                break;
            }
        }
        $this->errors[] = PMF_User::ERROR_USER_INCORRECT_PASSWORD;

        return false;
    }

    /**
     * Checks the number of entries of given login name
     *
     * @param  string $login        Loginname
     * @param  array  $optionslData Optional data
     * @return integer
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        $check = sprintf("
            SELECT
                login
            FROM
                %sfaquserlogin
            WHERE
                login = '%s'",
            SQLPREFIX,
            $this->db->escape($login)
        );
            
        $check = $this->db->query($check);
        $error = $this->db->error();
        
        if (strlen($error) > 0) {
            $this->errors[] = $error;
            return 0;
        }

        return $this->db->numRows($check);
    }
}
