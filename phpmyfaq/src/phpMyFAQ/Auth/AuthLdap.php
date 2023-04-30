<?php

/**
 * Manages user authentication with LDAP server.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Alberto Cabello <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2009-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    private ?LdapCore $ldap = null;

    /** @var string[] Array of LDAP servers */
    private readonly array $ldapServer;

    /** @var int Active LDAP server */
    private int $activeServer = 0;

    /** @var bool */
    private readonly mixed $multipleServers;

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

        if (0 === (is_countable($this->ldapServer) ? count($this->ldapServer) : 0)) {
            throw new Exception('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->ldap = new LdapCore($this->config);
        $this->connect($this->activeServer);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, string $password, string $domain = ''): bool
    {
        $user = new User($this->config);
        $result = $user->createUser($login, '', $domain);

        $this->connect($this->activeServer);

        $user->setStatus('active');

        // Set user information from LDAP
        $user->setUserData(
            ['display_name' => $this->ldap->getCompleteName($login), 'email' => $this->ldap->getMail($login)]
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
     * @throws Exception
     */
    public function checkCredentials($login, $password, array $optionalData = null): bool
    {
        if ('' === trim((string) $password)) {
            $this->errors[] = User::ERROR_USER_INCORRECT_PASSWORD;
            return false;
        }

        // Get active LDAP server for current user
        if ($this->multipleServers) {
            // Try all LDAP servers
            foreach ($this->ldapServer as $key => $value) {
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $bindLogin = $login;
        if ($this->config->get('ldap.ldap_use_domain_prefix')) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->connect($this->activeServer);

            $bindLogin = $this->ldap->getDn($login);
        }

        // Check user in LDAP
        $this->ldap = new LdapCore($this->config);
        $this->ldap->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $bindLogin,
            htmlspecialchars_decode((string) $password)
        );

        if (!$this->ldap->bind($bindLogin, htmlspecialchars_decode((string) $password))) {
            $this->errors[] = $this->ldap->error;
            return false;
        } else {
            $this->create($login, htmlspecialchars_decode((string) $password));
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
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $this->connect($this->activeServer);

        return strlen((string) $this->ldap->getCompleteName($login));
    }

    private function connect(int $activeServer = 0): void
    {
        $this->ldap->connect(
            $this->ldapServer[$activeServer]['ldap_server'],
            $this->ldapServer[$activeServer]['ldap_port'],
            $this->ldapServer[$activeServer]['ldap_base'],
            $this->ldapServer[$activeServer]['ldap_user'],
            $this->ldapServer[$activeServer]['ldap_password']
        );

        if ($this->ldap->error) {
            $this->errors[] = $this->ldap->error;
        }
    }
}
