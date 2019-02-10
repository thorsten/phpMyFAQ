<?php

/**
 * Manages user authentication with Apache's SSO authentication, e.g. mod_sspi
 * or mod_auth_kerb.
 *
 * PHP Version 5.5
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2011-06-22
 */
if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Sso.
 *
 * @category  phpMyFAQ 
 *
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2019 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 *
 * @link      https://www.phpmyfaq.de
 * @since     2011-06-22
 */
class PMF_Auth_Sso extends PMF_Auth implements PMF_Auth_Driver
{
    /**
     * Always returns true because of SSO.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return bool
     */
    public function add($login, $pass)
    {
        if ($this->_config->get('security.ldapSupport')) {
            // LDAP + SSO
            $authLdap = new PMF_Auth_Ldap($this->_config);
            $result = $authLdap->add($login, $pass);

            return $result;
        } else {
            // LDAP disabled
            $user = new PMF_User($this->_config);
            $result = $user->createUser($login, null);

            if ($result) {
                $user->setStatus('active');
            }

            // Update user information
            $user->setUserData(
                array(
                    'display_name' => $login,
                )
            );

            return $result;
        }
    }

    /**
     * Always returns true because of SSO.
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return bool
     */
    public function changePassword($login, $pass)
    {
        return true;
    }

    /**
     * Always returns true because of SSO.
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
     * Checks if the username of the remote user is equal to the login name.
     *
     * @param string $login        Loginname
     * @param string $pass         Password
     * @param array  $optionalData Optional data
     *
     * @return bool
     */
    public function checkPassword($login, $pass, Array $optionalData = null)
    {
        if (!isset($_SERVER['REMOTE_USER'])) {
            return false;
        } else {
            // Check if "DOMAIN\user", "user@DOMAIN" or only "user"
            $remoteUser = explode('\\', $_SERVER['REMOTE_USER']);
            if (is_array($remoteUser) && count($remoteUser) > 1) {
                $user = $remoteUser[1];
            } else {
                $remoteUser = explode('@', $_SERVER['REMOTE_USER']);
                if (is_array($remoteUser) && count($remoteUser) > 1) {
                    $user = $remoteUser[0];
                } else {
                    $user = $_SERVER['REMOTE_USER'];
                }
            }
            if ($user === $login) {
                $this->add($login, $pass);

                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Returns 1, if $_SERVER['REMOTE_USER'] is set.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return int
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        return isset($_SERVER['REMOTE_USER']) ? 1 : 0;
    }
}
