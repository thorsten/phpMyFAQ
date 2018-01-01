<?php

namespace phpMyFAQ\Auth;

/**
 * Manages user authentication with LDAP server.
 *
 * PHP Version 5.6
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */

use phpMyFAQ\Auth;
use phpMyFAQ\Auth\Driver;
use phpMyFAQ\Configuration;
use phpMyFAQ\Exception;
use phpMyFAQ\Ldap as LdapCore;
use phpMyFAQ\User;

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Auth_Ldap.
 *
 * @category  phpMyFAQ
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2018 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2009-03-01
 */
class Ldap extends Auth implements Driver
{
    /**
     * LDAP connection handle.
     *
     * @var Ldap
     */
    private $ldap = null;

    /**
     * LDAP server(s).
     *
     * @var array
     */
    private $ldapServer = [];

    /**
     * Internal key of the active LDAP server where user was found.
     *
     * @var int
     */
    private $activeServer = 0;

    /**
     * Multiple LDAP servers.
     *
     * @var bool
     */
    private $multipleServers = false;

    /**
     * Constructor.
     *
     * @param Configuration $config
     *
     * @throws Exception
     */
    public function __construct(Configuration $config)
    {
        $this->_config = $config;
        $this->ldapServer = $this->_config->getLdapServer();
        $this->multipleServers = $this->_config->get('ldap.ldap_use_multiple_servers');

        parent::__construct($this->_config);

        if (0 === count($this->ldapServer)) {
            throw new Exception('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->ldap = new LdapCore($this->_config);
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
     * @param string $login    Login name
     * @param string $password Password
     *
     * @return bool
     */
    public function add($login, $password)
    {
        $user = new User($this->_config);
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
                'email' => $this->ldap->getMail($login),
            )
        );

        return $result;
    }

    /**
     * Does nothing. A function required to be a valid auth.
     *
     * @param string $login    Loginname
     * @param string $password Password
     *
     * @return bool
     */
    public function changePassword($login, $password)
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
     * @param string $password     Password
     * @param array  $optionalData Optional data
     *
     * @return bool
     */
    public function checkPassword($login, $password, Array $optionalData = null)
    {
        if ('' === trim($password)) {
            $this->errors[] = User::ERROR_USER_INCORRECT_PASSWORD;

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
                    $this->activeServer = (int) $key;
                    break;
                }
            }
        }

        $bindLogin = $login;
        if ($this->_config->get('ldap.ldap_use_domain_prefix')) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'].'\\'.$login;
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
        $this->ldap = new LdapCore($this->_config);
        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $bindLogin,
            htmlspecialchars_decode($password)
        );

        if (! $this->ldap->bind($bindLogin, htmlspecialchars_decode($password))) {
            $this->errors[] = $this->ldap->error;

            return false;
        } else {
            $this->add($login, htmlspecialchars_decode($password));

            return true;
        }
    }

    /**
     * Returns number of characters of name, 0 will be returned if it fails.
     *
     * @param string $login        Loginname
     * @param array  $optionalData Optional data
     *
     * @return int
     */
    public function checkLogin($login, Array $optionalData = null)
    {
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
                    $this->activeServer = (int) $key;
                    break;
                }
            }
        }

        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $this->ldapServer[$this->activeServer]['ldap_user'],
            $this->ldapServer[$this->activeServer]['ldap_password']
        );

        return strlen($this->ldap->getCompleteName($login));
    }
}
