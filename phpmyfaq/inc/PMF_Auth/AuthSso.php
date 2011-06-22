<?php
/**
 * Manages user authentication with Apache's SSO authentication, e.g. mod_sspi
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
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-06-22
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_AuthDriver
 *
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-06-22
 */
class PMF_Auth_AuthSso extends PMF_Auth implements PMF_Auth_AuthDriver
{
    /**
     * Adds a new user account to the authentication table.
     *
     * Returns true on success, otherwise false.
     *
     * @param  string $login Loginname
     * @param  string $pass  Password
     * @return boolean
     */
    public function add($login, $pass)
    {
        
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
        
    }
    
    /**
     * Checks the password for the given user account.
     *
     * Returns true if the given password for the user account specified by
     * is correct, otherwise false.
     * Error messages are added to the array errors.
     *
     * This function is only called when local authentication has failed, so
     * we are about to create user account.
     *
     * @param  string $login        Loginname
     * @param  string $pass         Password
     * @param  array  $optionslData Optional data
     * @return boolean
     */
    public function checkPassword($login, $pass, Array $optionalData = null)
    {
        if (!isset($_SERVER['REMOTE_USER'])) {
            return false;
        } else {
            if ($_SERVER['REMOTE_USER'] == $login) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param  string $login        Loginname
     * @param  array  $optionalData Optional data
     * @return integer
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        return isset($_SERVER['REMOTE_USER']) ? true : false;
    }
}