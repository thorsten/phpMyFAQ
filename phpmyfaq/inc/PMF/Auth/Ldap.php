<?php
/**
 * Manages user authentication with LDAP server.
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Auth
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
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
 * @package   Auth
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2014 phpMyFAQ Team
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
    private $_ldapConfig = [];

    /**
     * LDAP server(s)
     *
     * @var array
     */
    private $ldapServer = [];

    /**
     * Internal key of the active LDAP server where user was found
     * @var int
     */
    private $activeServer = 0;

    /**
     * Multiple LDAP servers
     *
     * @var boolean
     */
    private $multipleServers = false;
    
    /**
     * Constructor
     *
     * @param  PMF_Configuration $config
     *
     * @return PMF_Auth_Ldap
     */
    public function __construct(PMF_Configuration $config)
    {
        $this->_config         = $config;
        $this->_ldapConfig     = $this->_config->getLdapConfig();
        $this->ldapServer      = $this->_config->getLdapServer();
        $this->multipleServers = $this->_ldapConfig['ldap_use_multiple_servers'];
        
        parent::__construct($this->_config);
        
        $this->ldap = new PMF_Ldap($this->_config);
        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $this->ldapServer[$this->activeServer]['ldap_user'],
            $this->ldapServer[$this->activeServer]['ldap_password']
        );
        
        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
        } 
    }

    /**
     * Adds a new user account to the authentication table.
     * Returns true on success, otherwise false.
     *
     * @param  string $login Login name
     * @param  string $pass  Password
     *
     * @return boolean
     */
    public function add($login, $pass)
    {
        $user   = new PMF_User($this->_config);
        $result = $user->createUser($login, null);

        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $this->ldapServer[$this->activeServer]['ldap_user'],
            $this->ldapServer[$this->activeServer]['ldap_password']
        );
        
        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
        } 

        $user->setStatus('active');

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
     * @param array  $optionalData Optional data
     *
     * @return boolean
     */
    public function checkPassword($login, $pass, Array $optionalData = null)
    {
        if ('' === trim($pass)) {
            $this->errors[] = PMF_User::ERROR_USER_INCORRECT_PASSWORD;
            return false;
        }

        // Get active LDAP server for current user
        if ($this->multipleServers) {
            // Try all LDAP servers
            foreach ($this->ldapServer as $key => $value) {

                $this->ldap->connect(
                    $this->ldapServer[$key]['ldap_server'],
                    $this->ldapServer[$key]['ldap_port'],
                    $this->ldapServer[$key]['ldap_base'],
                    $this->ldapServer[$key]['ldap_user'],
                    $this->ldapServer[$key]['ldap_password']
                );
                if ($this->ldap->error) {
                    $this->errors[] = $this->ldap->error;
                }

                if (false !== $this->ldap->getDn($login)) {
                    $this->activeServer = $key;
                    break;
                }
            }
        }

        $bindLogin = $login;
        if ($this->_ldapConfig['ldap_use_domain_prefix']) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->ldap->connect(
                $this->ldapServer[$this->activeServer]['ldap_server'],
                $this->ldapServer[$this->activeServer]['ldap_port'],
                $this->ldapServer[$this->activeServer]['ldap_base'],
                $this->ldapServer[$this->activeServer]['ldap_user'],
                $this->ldapServer[$this->activeServer]['ldap_password']
            );
            if ($this->ldap->error) {
                $this->errors[] = $this->ldap->error;
            }
            
            $bindLogin = $this->ldap->getDn($login);
        }

        // Check user in LDAP
        $this->ldap = new PMF_Ldap($this->_config);
        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
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
     * @param array  $optionalData Optional data
     * 
     * @return integer
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        return 1;
    }
}
