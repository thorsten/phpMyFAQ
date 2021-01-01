<?php

/**
 * Manages user authentication with LDAP server.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Ldap as LdapCore;
use phpMyFAQ\User;

/**
 * Class AuthLdap
 *
 * @package phpMyFAQ\Auth
 */
class AuthLdap extends Auth implements AuthDriverInterface
{
    /** @var AuthLdap */
    private $ldap = null;

    /** @var array Array of LDAP servers */
    private $ldapServer;

    /** @var int Active LDAP server */
    private $activeServer = 0;

    /** @var bool */
    private $multipleServers;

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->ldapServer = $this->config->getLdapServer();
        $this->multipleServers = $this->config->get('ldap.ldap_use_multiple_servers');

        parent::__construct($this->config);

        if (0 === count($this->ldapServer)) {
            throw new Exception('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->ldap = new LdapCore($this->config);
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
     * @inheritDoc
     */
    public function create($login, $password, $domain = ''): bool
    {
        $user = new User($this->config);
        $result = $user->createUser($login, null, $domain);

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

        // Set user information from LDAP
        $user->setUserData(
            array(
                'display_name' => $this->ldap->getCompleteName($login),
                'email' => $this->ldap->getMail($login),
            )
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update($login, $password): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete($login): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function checkCredentials($login, $password, array $optionalData = null): bool
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
                    $this->activeServer = (int)$key;
                    break;
                }
            }
        }

        $bindLogin = $login;
        if ($this->config->get('ldap.ldap_use_domain_prefix')) {
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
        $this->ldap = new LdapCore($this->config);
        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $bindLogin,
            htmlspecialchars_decode($password)
        );

        if (!$this->ldap->bind($bindLogin, htmlspecialchars_decode($password))) {
            $this->errors[] = $this->ldap->error;
            return false;
        } else {
            $this->create($login, htmlspecialchars_decode($password));
            return true;
        }
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin($login, array $optionalData = null): int
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
                    $this->activeServer = (int)$key;
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
