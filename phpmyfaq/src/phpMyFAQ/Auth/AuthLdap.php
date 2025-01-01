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
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2009-03-01
 */

namespace phpMyFAQ\Auth;

use phpMyFAQ\Auth;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Ldap as LdapCore;
use phpMyFAQ\User;
use SensitiveParameter;

/**
 * Class AuthLdap
 *
 * @package phpMyFAQ\Auth
 */
class AuthLdap extends Auth implements AuthDriverInterface
{
    private ?LdapCore $ldapCore = null;

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
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->ldapServer = $this->configuration->getLdapServer();
        $this->multipleServers = $this->configuration->get('ldap.ldap_use_multiple_servers');

        parent::__construct($this->configuration);

        if (0 === (is_countable($this->ldapServer) ? count($this->ldapServer) : 0)) {
            throw new AuthException('An error occurred while contacting LDAP: No configuration found.');
        }

        $this->ldapCore = new LdapCore($this->configuration);
        $this->connect($this->activeServer);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $login, #[SensitiveParameter] string $password, string $domain = ''): bool
    {
        $user = new User($this->configuration);
        $result = $user->createUser($login, '', $domain);

        $this->connect($this->activeServer);

        $user->setStatus('active');
        $user->setAuthSource(AuthenticationSourceType::AUTH_LDAP->value);

        // Set user information from LDAP
        $user->setUserData(
            [
                'display_name' => $this->ldapCore->getCompleteName($login),
                'email' => $this->ldapCore->getMail($login)
            ]
        );

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function update(string $login, #[SensitiveParameter] string $password): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $login): bool
    {
        return true;
    }

    /**
     * @inheritDoc
     * @throws AuthException|Exception
     */
    public function checkCredentials(
        string $login,
        #[SensitiveParameter] string $password,
        ?array $optionalData = null
    ): bool {
        if ('' === trim($password)) {
            throw new AuthException(User::ERROR_USER_INCORRECT_PASSWORD);
        }

        // Get active LDAP server for current user
        if ($this->multipleServers) {
            // Try all LDAP servers
            foreach (array_keys($this->ldapServer) as $key) {
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $bindLogin = $login;
        if ($this->configuration->get('ldap.ldap_use_domain_prefix')) {
            if (array_key_exists('domain', $optionalData)) {
                $bindLogin = $optionalData['domain'] . '\\' . $login;
            }
        } else {
            $this->connect($this->activeServer);

            $bindLogin = $this->ldapCore->getDn($login);
        }

        // Check user in LDAP
        $this->ldapCore = new LdapCore($this->configuration);
        $this->ldapCore->connect(
            $this->ldapServer[$this->activeServer]['ldap_server'],
            $this->ldapServer[$this->activeServer]['ldap_port'],
            $this->ldapServer[$this->activeServer]['ldap_base'],
            $bindLogin,
            htmlspecialchars_decode($password)
        );

        if (!$this->ldapCore->bind($bindLogin, htmlspecialchars_decode($password))) {
            throw new AuthException($this->ldapCore->error);
        }

        $this->create($login, htmlspecialchars_decode($password));

        return true;
    }

    /**
     * @inheritDoc
     */
    public function isValidLogin(string $login, ?array $optionalData = null): int
    {
        // Get active LDAP server for current user
        if ($this->multipleServers) {
            // Try all LDAP servers
            foreach (array_keys($this->ldapServer) as $key) {
                $this->connect($key);
                $this->activeServer = (int)$key;
                break;
            }
        }

        $this->connect($this->activeServer);

        return strlen((string) $this->ldapCore->getCompleteName($login));
    }

    private function connect(int $activeServer = 0): void
    {
        $this->ldapCore->connect(
            $this->ldapServer[$activeServer]['ldap_server'],
            $this->ldapServer[$activeServer]['ldap_port'],
            $this->ldapServer[$activeServer]['ldap_base'],
            $this->ldapServer[$activeServer]['ldap_user'],
            $this->ldapServer[$activeServer]['ldap_password']
        );

        if ($this->ldapCore->error) {
            $this->configuration->getLogger()->error($this->ldapCore->error);
            $this->errors[] = $this->ldapCore->error;
        }
    }
}
