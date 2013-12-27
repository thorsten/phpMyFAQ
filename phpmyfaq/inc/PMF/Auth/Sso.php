<?php
/**
 * Manages user authentication with Apache's SSO authentication, e.g. mod_sspi
 * or mod_auth_kerb
 *
 * PHP Version 5.4
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ 
 * @package   Auth
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-06-22
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Sso
 *
 * @category  phpMyFAQ 
 * @package   Auth
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2011-2013 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2011-06-22
 */
class PMF_Auth_Sso extends PMF_Auth implements PMF_Auth_Driver
{
    /**
     * Always returns true because of SSO
     *
     * @param string $login Loginname
     * @param string $pass  Password
     *
     * @return boolean
     */
    public function add($login, $pass)
    {
        return true;
    }

    /**
     * Always returns true because of SSO
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
     * Always returns true because of SSO
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
     * Checks if the username of the remote user is equal to the login name
     *
     * @param string $login        Loginname
     * @param string $pass         Password
     * @param array  $optionalData Optional data
     *
     * @return boolean
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
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * Returns true, if $_SERVER['REMOTE_USER'] is set.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return boolean
     */
    public function checkLogin($login, Array $optionalData = null)
    {
        return isset($_SERVER['REMOTE_USER']) ? true : false;
    }
}