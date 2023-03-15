<?php

/**
 * The PMF_Ldap class provides methods and functions for a LDAP database.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2022 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-12-16
 */

namespace phpMyFAQ;

/**
 * Class Ldap
 *
 * @package phpMyFAQ
 */
class Ldap
{
    /**
     * Error.
     *
     * @var string|null
     */
    public ?string $error = null;

    /**
     * LDAP error number.
     *
     * @var int|null
     */
    public ?int $errno = null;

    /**
     * @var array|string[]
     */
    private array $ldapConfig;

    /**
     * @var Configuration
     */
    private Configuration $config;

    /**
     * An LDAP link identifier, returned by ldap_connect()
     *
     * @var resource|false
     */
    private $ds;

    /**
     * The LDAP base.
     *
     * @var string|null
     */
    private ?string $base = null;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->ldapConfig = $this->config->getLdapConfig();
    }

    /**
     * Connects to given LDAP server with given credentials.
     *
     * @param string $ldapServer
     * @param int $ldapPort
     * @param string $ldapBase
     * @param string $ldapUser
     * @param string $ldapPassword
     * @return bool
     */
    public function connect(
        string $ldapServer,
        int $ldapPort,
        string $ldapBase,
        string $ldapUser = '',
        string $ldapPassword = ''
    ): bool {
        // Sanity checks
        if ('' === $ldapServer || '' === $ldapBase) {
            return false;
        }

        $this->base = $ldapBase;
        $this->ds = ldap_connect($ldapServer . ':' . $ldapPort);

        if (!$this->ds) {
            $this->error = sprintf(
                'Unable to connect to LDAP server (Error: %s)',
                ldap_error($this->ds)
            );
            $this->errno = ldap_errno($this->ds);

            return false;
        }

        // Set LDAP options
        foreach ($this->config->getLdapOptions() as $key => $value) {
            if (!ldap_set_option($this->ds, constant($key), $value)) {
                $this->errno = ldap_errno($this->ds);
                $this->error = sprintf(
                    'Unable to set LDAP option "%s" to "%s" (Error: %s).',
                    $key,
                    $value,
                    ldap_error($this->ds)
                );

                return false;
            }
        }

        if ($this->config->get('ldap.ldap_use_dynamic_login')) {
            // Check for dynamic user binding
            $ldapRdn = $this->config->get('ldap.ldap_dynamic_login_attribute') . '=' . $ldapUser . ',' . $ldapBase;
            $ldapBind = $this->bind($ldapRdn, $ldapPassword);
        } elseif ($this->config->get('ldap.ldap_use_anonymous_login')) {
            // Check for anonymous binding
            $ldapBind = $this->bind();
        } else {
            // Check for user binding without RDN
            $ldapBind = $this->bind($ldapUser, $ldapPassword);
        }

        if (false === $ldapBind) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to bind to LDAP server (Error: %s).',
                ldap_error($this->ds)
            );
            $this->ds = false;

            return false;
        }

        return true;
    }

    /**
     * Binds to the LDAP directory with specified RDN and password.
     *
     * @param string $rdn
     * @param string $password
     *
     * @return bool
     */
    public function bind(string $rdn = '', string $password = ''): bool
    {
        if ($this->ds === false) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        if ('' === $rdn && '' === $password) {
            return ldap_bind($this->ds);
        } else {
            return ldap_bind($this->ds, $rdn, $password);
        }
    }

    /**
     * Returns the user's email address from LDAP.
     *
     * @param string $username Username
     * @return string
     */
    public function getMail(string $username)
    {
        return $this->getLdapData($username, 'mail');
    }

    /**
     * Returns specific data from LDAP.
     *
     * @param string $username Username
     * @param string $data     MapKey
     * @return string|false
     */
    private function getLdapData(string $username, string $data)
    {
        if ($this->ds === false) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        if (!array_key_exists($data, $this->ldapConfig['ldap_mapping'])) {
            $this->error = sprintf(
                'The requested data field "%s" does not exist in LDAP mapping configuration.',
                $data
            );

            return false;
        }

        $filter = sprintf(
            '(%s=%s)',
            $this->config->get('ldap.ldap_mapping.username'),
            $this->quote($username)
        );

        if ($this->config->get('ldap.ldap_use_memberOf')) {
            $filter = sprintf(
                '(&%s(memberOf:1.2.840.113556.1.4.1941:=%s))',
                $filter,
                $this->config->get('ldap.ldap_mapping.memberOf')
            );
        }

        $fields = [$this->ldapConfig['ldap_mapping'][$data]];

        $searchResult = ldap_search($this->ds, $this->base, $filter, $fields);

        if (!$searchResult) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );

            return false;
        }

        $entryId = ldap_first_entry($this->ds, $searchResult);

        if (!$entryId) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );

            return false;
        }

        $entries = ldap_get_entries($this->ds, $searchResult);
        for ($i = 0; $i < $entries['count']; $i++) {
            if (isset($entries[$i][$fields[0]][0])) {
                return $entries[$i][$fields[0]][0];
            }
        }

        return false;
    }

    /**
     * Quotes LDAP strings in accordance with the RFC 2254.
     *
     * @param string $string
     * @return string
     */
    public function quote(string $string): string
    {
        return str_replace(
            ['\\', ' ', '*', '(', ')'],
            ['\\5c', '\\20', '\\2a', '\\28', '\\29'],
            $string
        );
    }

    /**
     * Returns the user's DN.
     *
     * @param string $username Username
     * @return string
     */
    public function getDn(string $username)
    {
        return $this->getLdapDn($username);
    }

    /**
     * Returns the DN from LDAP.
     *
     * @param string $username Username
     * @return string|false
     */
    private function getLdapDn(string $username)
    {
        if ($this->ds === false) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        $filter = sprintf(
            '(%s=%s)',
            $this->config->get('ldap.ldap_mapping.username'),
            $this->quote($username)
        );
        $sr = ldap_search($this->ds, $this->base, $filter);

        if (false === $sr) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Unable to search for "%s" (Error: %s)',
                $username,
                ldap_error($this->ds)
            );

            return false;
        }

        $entryId = ldap_first_entry($this->ds, $sr);

        if (false === $entryId) {
            $this->errno = ldap_errno($this->ds);
            $this->error = sprintf(
                'Cannot get the value(s). Error: %s',
                ldap_error($this->ds)
            );

            return false;
        }

        return ldap_get_dn($this->ds, $entryId);
    }

    /**
     * Returns the user's full name from LDAP.
     *
     * @param string $username Username
     * @return string
     */
    public function getCompleteName(string $username)
    {
        return $this->getLdapData($username, 'name');
    }

    /**
     * Returns the LDAP error message of the last LDAP command.
     *
     * @param resource $ds LDAP resource
     *
     * @return string
     */
    public function error($ds = null): string
    {
        if ($ds === null) {
            $ds = $this->ds;
        }

        return ldap_error($ds);
    }
}
