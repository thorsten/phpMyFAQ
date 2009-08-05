<?php
/**
 * Interface for managing user authentication
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_Auth
 * @author     Thorsten Rinne <thorsten@phpmyfaq.de>
 * @since      2009-03-03
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id: AuthDb.php 3790 2009-02-10 20:43:36Z thorsten $ 
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
 * PMF_Auth_AuthDriver
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_Auth
 * @author     Alberto Cabello <alberto@unex.es>
 * @since      2009-03-01
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id: AuthDb.php 3790 2009-02-10 20:43:36Z thorsten $ 
 */
interface PMF_Auth_AuthDriver
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
    public function add($login, $pass);

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
    public function changePassword($login, $pass);
    
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
    public function delete($login);
    
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
     * @param  string $login       Loginname
     * @param  string $pass        Password
     * @param  array  $optionlData Optional data
     * @return boolean
     */
    public function checkPassword($login, $pass, Array $optionlData = array());

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param  string $login Loginname
     * @param  array  $optionlData Optional data
     * @return integer
     */
    public function checkLogin($login, Array $optionlData = array());
}