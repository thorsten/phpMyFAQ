<?php
/**
 * Manages user authentication with LDAP server.
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Alberto Cabello <alberto@unex.es>
 * @since      2009-03-01
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
 * PMF_User_AuthLdap
 *
 * @package    phpMyFAQ 
 * @subpackage PMF_User
 * @author     Alberto Cabello <alberto@unex.es>
 * @since      2009-03-01
 * @copyright  2009 phpMyFAQ Team
 * @version    SVN: $Id: AuthDb.php 3790 2009-02-10 20:43:36Z thorsten $ 
 */
class PMF_User_AuthLdap extends PMF_User_Auth
{
	/**
	 * LDAP connection handle
	 *
	 * @var PMF_Ldap
	 */
    private $ldap = null;
    
    /**
     * Constructor
     *
     * @param  string  $enctype   Type of encoding
     * @param  boolean $read_only Readonly?
     * @return void
     */
    public function __construct($enctype = 'none', $read_only = false)
    {
        $this->selectEncType($enctype);
        $this->setReadOnly($read_only);
    }

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
        $user   = new PMF_User_User();
        $result = $user->createUser($login, null);
        
        // Update user information from LDAP
		$user->setUserData(array('display_name' => $this->ldap->ldap_getCompleteName($login),
                                 'email'        => $this->ldap->ldap_getMail($login)));
        return $result;
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
     * @param  string $login Loginname
     * @param  string $pass  Password
     * @return boolean
     */
    public function checkPassword($login, $pass)
    {
        global $PMF_LDAP;
        $this->ldap = new PMF_Ldap($PMF_LDAP['ldap_server'],
                                   $PMF_LDAP['ldap_port'],
                                   $PMF_LDAP['ldap_base'],
                                   $login, 
                                   $pass);
        if ($this->ldap->error) {
            $this->errors[] = PMF_USERERROR_INCORRECT_PASSWORD;
            return false;
        } else {
            $this->add($login, $pass);
            return true;
        }
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param  string $table           Table for login credentials
     * @param  string $login_column    Login name column
     * @param  string $password_column Password column
     * @return void
     */
    public function connect($table = '', $login_column = '', $password_column = '')
    {
        return true;
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param  string $login Loginname
     * @return integer
     */
    public function checkLogin($login)
    {
        return 1;
    }

}