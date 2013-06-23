<?php
/**
 * Manages user authentication with LDAP server.
 * 
 * PHP version 5.2
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
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_AuthLdap
 *
 * @category  phpMyFAQ 
 * @package   PMF_Auth
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */
class PMF_Auth_AuthLdap extends PMF_Auth implements PMF_Auth_AuthDriver
{
    /**
     * LDAP connection handle
     *
     * @var PMF_Ldap
     */
    private $ldap = null;

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
        global $PMF_LDAP;

        $this->multipleServers = $PMF_LDAP['ldap_use_multiple_servers'];
        
        parent::__construct($enctype, $read_only);
        
       // $this->ldap = new PMF_Ldap(
            // $PMF_LDAP['ldap_server'][0],
            // $PMF_LDAP['ldap_port'][0],
            // $PMF_LDAP['ldap_base'][0],
            // $PMF_LDAP['ldap_user'][0],
            // $PMF_LDAP['ldap_password'][0]
        // );
        
        // if ($this->ldap->error) {
            // $this->errors[] = $this->ldap->error;
        // }
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
        $user = new PMF_User();
        $user->setLoginMinLength(2); // LDAP user names can be very short!

        $result = $user->createUser($login, null);
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
     * @param array  $optionslData Optional data
     * 
     * @return boolean
     */
    public function checkPassword($login, $pass, Array $optionalData = null)
    {
        global $PMF_LDAP;

        if ('' === trim($pass)) {
            $this->errors[] = PMF_User::ERROR_USER_INCORRECT_PASSWORD;
            return false;
        }

        $bindLogin = "wrongstring";
        
        //BMU
        //domain\user
        //user@domain-FQDN
        //user
        
        // key PMF_LDAP
        $keyDomain = 0;
        
        if (!empty($PMF_LDAP['ldap_base'][1])) {    // multiple basedn definded? or $this->multipleServers
            
            foreach($PMF_LDAP['ldap_base'] as $key => $value) {
                //user provided domain name with login
                if (array_key_exists('domain', $optionalData)) {
                    
                    $value = strtolower(str_replace(' ','',$value));
                    
                    // get domain portion from basedn
                    if (($pos = strpos($value,'dc=')) !== false) {
                    
                        $value = substr($value,$pos+3);
                        $value = str_replace(',dc=','.',$value);

                        if ($optionalData['domain'] == $value) {
                            $keyDomain = $key;
                            break; // domain-FQDN or single dc= part
                        }
                        else if (($pos = strpos($value,'.')) !== false) {
                            if ($optionalData['domain'] == substr($value,0,$pos)) {
                            $keyDomain = $key;
                                break; // domain
                            }
                        }
                    }
                }
				//no domain provided - try
                else {
                    $this->ldap = new PMF_Ldap(
                        $PMF_LDAP['ldap_server'][$key],
                        $PMF_LDAP['ldap_port'][$key],
                        $PMF_LDAP['ldap_base'][$key],
                        $PMF_LDAP['ldap_user'][$key],
                        $PMF_LDAP['ldap_password'][$key]
                    );
                    if ($this->ldap->error) {
                        $this->errors[] = $this->ldap->error;
                    }
                    
                    if (($bindLogin = $this->ldap->getDn($login)) != false) {
                        $keyDomain = $key;
                        break;
                    }
                }
            }
        }
        
        // get DN from user
        // use this->multipleServers for backward comp?
        $this->ldap = new PMF_Ldap(
            $PMF_LDAP['ldap_server'][$keyDomain],
            $PMF_LDAP['ldap_port'][$keyDomain],
            $PMF_LDAP['ldap_base'][$keyDomain],
            $PMF_LDAP['ldap_user'][$keyDomain],
            $PMF_LDAP['ldap_password'][$keyDomain]
        );
        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
        }

        if (($bindLogin = $this->ldap->getDn($login)) == false){
            // user not found
            return false;
        }
        
/*        if ($PMF_LDAP['ldap_use_domain_prefix']) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->ldap = new PMF_Ldap(
                $PMF_LDAP['ldap_server'],
                $PMF_LDAP['ldap_port'],
                $PMF_LDAP['ldap_base'],
                $PMF_LDAP['ldap_user'],
                $PMF_LDAP['ldap_password']
            );
            if ($this->ldap->error) {
                $this->errors[] = $this->ldap->error;
            }
            
            $bindLogin = $this->ldap->getDn($login);
        }*/

        // Check user in LDAP
        $this->ldap = new PMF_Ldap(
            $PMF_LDAP['ldap_server'][$keyDomain],
            $PMF_LDAP['ldap_port'][$keyDomain],
            $PMF_LDAP['ldap_base'][$keyDomain],
            $bindLogin,
            $pass
        );
//BMU-END        
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
     * @return string
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        //BMU
        return true;//$this->ldap->getCompleteName($login);
        
        //because:
        //CurrentUser.php
        // $login does not exist, so continue
            // if (!$auth->checkLogin($login, $optData)) {
                // $login_error++;
                // continue;
            // }
        // checkLogin calls getLdapData which uses the first LDAP server from PMF_Auth_AuthLdap __construct
        // this server may not authorize the user
        // just retrun true or test domain first in checkLogin like checkPassword
        
        //BMU-END
        //return $this->ldap->getCompleteName($login);
    }
}