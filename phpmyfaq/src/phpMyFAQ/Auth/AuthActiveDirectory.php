<?php

/**
 * Manages user authentication with Microsoft Active Directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2021-0513
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
class AuthActiveDirectory extends Auth implements AuthDriverInterface
{
    /** @var LdapCore */
    private $ldap = null;

    /** @var string[] Array of AD servers */
    private $activeDirectoryServers;

    /** @var int Active AD server */
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
        $this->activeDirectoryServers = $this->config->getLdapServer();
        $this->multipleServers = $this->config->get('ad.ad_use_multiple_servers');

        parent::__construct($this->config);

        if (0 === count($this->activeDirectoryServers)) {
            throw new Exception('An error occurred while contacting Active Directory: No configuration found.');
        }

        $this->ldap = new LdapCore($this->config);
        $this->ldap->connect(
            $this->activeDirectoryServers[$this->activeServer]['ad_server'],
            $this->activeDirectoryServers[$this->activeServer]['ad_port'],
            $this->activeDirectoryServers[$this->activeServer]['ad_base'],
            $this->activeDirectoryServers[$this->activeServer]['ad_user'],
            $this->activeDirectoryServers[$this->activeServer]['ad_password']
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
            $this->activeDirectoryServers[$this->activeServer]['ad_server'],
            $this->activeDirectoryServers[$this->activeServer]['ad_port'],
            $this->activeDirectoryServers[$this->activeServer]['ad_base'],
            $this->activeDirectoryServers[$this->activeServer]['ad_user'],
            $this->activeDirectoryServers[$this->activeServer]['ad_password']
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
            foreach ($this->activeDirectoryServers as $key => $value) {
                $this->ldap->connect(
                    $this->activeDirectoryServers[$key]['ad_server'],
                    $this->activeDirectoryServers[$key]['ad_port'],
                    $this->activeDirectoryServers[$key]['ad_base'],
                    $this->activeDirectoryServers[$key]['ad_user'],
                    $this->activeDirectoryServers[$key]['ad_password']
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
        if ($this->config->get('ad.ad_use_domain_prefix')) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->ldap->connect(
                $this->activeDirectoryServers[$this->activeServer]['ad_server'],
                $this->activeDirectoryServers[$this->activeServer]['ad_port'],
                $this->activeDirectoryServers[$this->activeServer]['ad_base'],
                $this->activeDirectoryServers[$this->activeServer]['ad_user'],
                $this->activeDirectoryServers[$this->activeServer]['ad_password']
            );
            if ($this->ldap->error) {
                $this->errors[] = $this->ldap->error;
            }

            $bindLogin = $this->ldap->getDn($login);
        }

        // Check user in LDAP
        $this->ldap = new LdapCore($this->config);
        $this->ldap->connect(
            $this->activeDirectoryServers[$this->activeServer]['ad_server'],
            $this->activeDirectoryServers[$this->activeServer]['ad_port'],
            $this->activeDirectoryServers[$this->activeServer]['ad_base'],
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
            foreach ($this->activeDirectoryServers as $key => $value) {
                $this->ldap->connect(
                    $this->activeDirectoryServers[$key]['ad_server'],
                    $this->activeDirectoryServers[$key]['ad_port'],
                    $this->activeDirectoryServers[$key]['ad_base'],
                    $this->activeDirectoryServers[$key]['ad_user'],
                    $this->activeDirectoryServers[$key]['ad_password']
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
            $this->activeDirectoryServers[$this->activeServer]['ad_server'],
            $this->activeDirectoryServers[$this->activeServer]['ad_port'],
            $this->activeDirectoryServers[$this->activeServer]['ad_base'],
            $this->activeDirectoryServers[$this->activeServer]['ad_user'],
            $this->activeDirectoryServers[$this->activeServer]['ad_password']
        );

        return strlen($this->ldap->getCompleteName($login));
    }
}
