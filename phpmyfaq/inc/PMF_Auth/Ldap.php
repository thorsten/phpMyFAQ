<?php
/**
 * Manages user authentication with LDAP server.
 *
 * PHP version 5.2
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Ldap
 *
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */
class PMF_Auth_Ldap extends PMF_Auth implements PMF_Auth_Driver
{
    /**
     * LDAP connection handle
     *
     * @var PMF_Ldap
     */
    private $ldap = null;

    /**
     * LDAP configuration
     *
     * @var array
     */
    private $_ldapConfig = array();

    /**
     * Multiple LDAP servers
     *
     * @var boolean
     */
    private $multipleServers = false;
    
    /**
     * Constructor
     *
     * @param string  $enctype   Type of encoding
     * @param boolean $read_only Readonly?
     *
     * @return PMF_Auth_AuthLdap
     */
    public function __construct($enctype = 'none', $read_only = false)
    {
        $this->_ldapConfig     = $this->_config->getLdapConfig();
        $this->multipleServers = $this->_ldapConfig['ldap_use_multiple_servers'];
        
        parent::__construct($enctype, $read_only);
        
        $this->ldap = new PMF_Ldap($this->_config);
        $this->ldap->connect(
            $this->_ldapConfig['ldap_server'],
            $this->_ldapConfig['ldap_port'],
            $this->_ldapConfig['ldap_base'],
            $this->_ldapConfig['ldap_user'],
            $this->_ldapConfig['ldap_password']
        );
        
        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
        } 
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
        $user   = new PMF_User($this->_config);
        $result = $user->createUser($login, null);
        
        // Update user information from LDAP
        $user->setUserData(
            array(
                'display_name' => $this->ldap->getCompleteName($login),
                'email'        => $this->ldap->getMail($login)
            )
        );
        return $result;
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return boolean
    */
    public function changePassword($login, $pass)
    {
        return true;
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login Loginname
     *
     * @return bool
     */
    public function delete($login)
    {
        return true;
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
     * @param string $login        Loginname
     * @param string $pass         Password
     * @param array  $optionslData Optional data
     *
     * @return boolean
     */
    public function checkPassword($login, $pass, Array $optionalData = null)
    {
        $bindLogin = $login;
        if ($this->_ldapConfig['ldap_use_domain_prefix']) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->ldap = new PMF_Ldap($this->_config);
            $this->ldap->connect(
                $this->_ldapConfig['ldap_server'],
                $this->_ldapConfig['ldap_port'],
                $this->_ldapConfig['ldap_base'],
                $this->_ldapConfig['ldap_user'],
                $this->_ldapConfig['ldap_password']
            );
            if ($this->ldap->error) {
                $this->errors[] = $this->ldap->error;
            }
            
            $bindLogin = $this->ldap->getDn($login);
        }

        // Check user in LDAP
        $this->ldap = new PMF_Ldap($this->_config);
        $this->ldap->connect(
            $this->_ldapConfig['ldap_server'],
            $this->_ldapConfig['ldap_port'],
            $this->_ldapConfig['ldap_base'],
            $bindLogin,
            $pass
        );
        
        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
            return false;
        } else {
            $this->add($login, $pass);
            return true;
        }
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login        Loginname
     * @param array  $optionslData Optional data
     *
     * @return integer
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        return $this->ldap->getCompleteName($login);
    }
}