<?php

/**
 * The Ldap class provides methods and functions for LDAP and/or Active Directory.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Adam Greene <phpmyfaq@skippy.fastmail.fm>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Alberto Cabello Sanchez <alberto@unex.es>
 * @author    Lars Scheithauer <larsscheithauer@googlemail.com>
 * @copyright 2004-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2004-12-16
 */

declare(strict_types=1);

namespace phpMyFAQ;

use LDAP\Connection;
use SensitiveParameter;

/**
 * Class Ldap
 *
 * @package phpMyFAQ
 */
class Ldap
{
    /**
     * Error.
     */
    public ?string $error = null;

    /**
     * LDAP error number.
     */
    public ?int $errno = null;

    private readonly array $ldapConfig;

    /**
     * The LDAP connection, present after a successful connect().
     */
    private ?Connection $ds = null;

    /**
     * The LDAP base.
     */
    private ?string $base = null;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->ldapConfig = $this->configuration->getLdapConfig();
    }

    /**
     * Connects to a given LDAP server with given credentials.
     */
    public function connect(
        string $ldapServer,
        int $ldapPort,
        string $ldapBase,
        string $ldapUser = '',
        #[SensitiveParameter]
        string $ldapPassword = '',
    ): bool {
        // Sanity checks
        if ('' === $ldapServer || '' === $ldapBase) {
            return false;
        }

        $this->base = $ldapBase;
        $connection = ldap_connect($ldapServer . ':' . $ldapPort);

        if (!$connection instanceof Connection) {
            $this->error = 'Unable to connect to LDAP server';
            $this->ds = null;

            return false;
        }

        $this->ds = $connection;

        // Set LDAP options
        foreach ($this->configuration->getLdapOptions() as $key => $ldapOption) {
            if (ldap_set_option($connection, (int) constant($key), $ldapOption)) {
                continue;
            }

            $this->errno = ldap_errno($connection);
            $errorMessage = 'Unable to set LDAP option "%s" to "%s" (Error: %s).';
            $this->error = sprintf($errorMessage, $key, print_r($ldapOption, return: true), ldap_error($connection));

            return false;
        }

        $ldapBind = match (true) {
            true === $this->configuration->get(item: 'ldap.ldap_use_dynamic_login') => $this->bind(
                (string) $this->configuration->get(item: 'ldap.ldap_dynamic_login_attribute')
                . '='
                . $ldapUser
                . ','
                . $ldapBase,
                $ldapPassword,
            ),
            true === $this->configuration->get(item: 'ldap.ldap_use_anonymous_login') => $this->bind(),
            default => $this->bind($ldapUser, $ldapPassword),
        };

        if (false === $ldapBind) {
            $this->errno = ldap_errno($connection);
            $this->error = sprintf('Unable to bind to LDAP server (Error: %s).', ldap_error($connection));
            $this->ds = null;

            return false;
        }

        return true;
    }

    /**
     * Binds to the LDAP directory with specified RDN and password.
     */
    public function bind(string $rdn = '', #[SensitiveParameter] string $password = ''): bool
    {
        if (!$this->ds instanceof Connection) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        if ('' === $rdn && '' === $password) {
            return @ldap_bind($this->ds, dn: null, password: null);
        }

        return @ldap_bind($this->ds, $rdn, $password);
    }

    /**
     * Returns the user's email address from LDAP.
     *
     * @param string $username Username
     */
    public function getMail(string $username): bool|string
    {
        return $this->getLdapData($username, data: 'mail');
    }

    /**
     * Returns specific data from LDAP.
     *
     * @param string $username Username
     * @param string $data     MapKey
     */
    private function getLdapData(string $username, string $data): bool|string
    {
        $connection = $this->ds;
        if (!$connection instanceof Connection) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        $base = $this->base;
        if ($base === null || $base === '') {
            $this->error = 'LDAP base DN is not configured.';

            return false;
        }

        $mapping = $this->ldapConfig['ldap_mapping'] ?? null;
        if (!is_array($mapping) || !array_key_exists($data, $mapping)) {
            $errorMessage = 'The requested data field "%s" does not exist in LDAP mapping configuration.';
            $this->error = sprintf($errorMessage, $data);

            return false;
        }

        $comparison = '(%s=%s)';
        $filter = sprintf(
            $comparison,
            (string) $this->configuration->get(item: 'ldap.ldap_mapping.username'),
            $this->quote($username),
        );

        if ($this->configuration->get(item: 'ldap.ldap_use_memberOf')) {
            $comparison = '(&%s(memberOf:1.2.840.113556.1.4.1941:=%s))';
            $filter = sprintf(
                $comparison,
                $filter,
                (string) $this->configuration->get(item: 'ldap.ldap_mapping.memberOf'),
            );
        }

        $field = (string) $mapping[$data];

        $searchResult = @ldap_search($connection, $base, $filter, [$field]);

        if (!$searchResult || is_array($searchResult)) {
            $errorMessage = 'Unable to search for "%s" (Error: %s)';
            $this->error = sprintf($errorMessage, $username, ldap_error($connection));

            return false;
        }

        $entryId = ldap_first_entry($connection, $searchResult);

        if (!$entryId) {
            $this->errno = ldap_errno($connection);
            $this->error = sprintf('Cannot get the value(s). Error: %s', ldap_error($connection));

            return false;
        }

        $entries = ldap_get_entries($connection, $searchResult);
        if ($entries === false) {
            return false;
        }

        for ($i = 0; $i < (int) $entries['count']; ++$i) {
            if (
                !array_key_exists($i, $entries)
                || !is_array($entries[$i])
                || !array_key_exists($field, $entries[$i])
                || !is_array($entries[$i][$field])
                || !array_key_exists(0, $entries[$i][$field])
            ) {
                continue;
            }

            return (string) $entries[$i][$field][0];
        }

        return false;
    }

    /**
     * Quotes LDAP strings in accordance with the RFC 2254.
     */
    public function quote(string $string): string
    {
        return str_replace(['\\', ' ', '*', '(', ')'], ['\\5c', '\\20', '\\2a', '\\28', '\\29'], $string);
    }

    /**
     * Returns the user's DN.
     *
     * @param string $username Username
     */
    public function getDn(string $username): bool|string
    {
        return $this->getLdapDn($username);
    }

    /**
     * Returns the DN from LDAP.
     *
     * @param string $username Username
     */
    private function getLdapDn(string $username): string|false
    {
        $connection = $this->ds;
        if (!$connection instanceof Connection) {
            $this->error = 'The LDAP connection handler is not a valid resource.';

            return false;
        }

        $base = $this->base;
        if ($base === null || $base === '') {
            $this->error = 'LDAP base DN is not configured.';

            return false;
        }

        $comparison = '(%s=%s)';
        $filter = sprintf(
            $comparison,
            (string) $this->configuration->get(item: 'ldap.ldap_mapping.username'),
            $this->quote($username),
        );
        $sr = @ldap_search($connection, $base, $filter);

        if (false === $sr || is_array($sr)) {
            $errorMessage = 'Unable to search for "%s" (Error: %s)';
            $this->error = sprintf($errorMessage, $username, ldap_error($connection));

            return false;
        }

        $entryId = ldap_first_entry($connection, $sr);

        if (false === $entryId) {
            $this->error = sprintf('Cannot get the value(s). Error: %s', ldap_error($connection));

            return false;
        }

        return ldap_get_dn($connection, $entryId);
    }

    /**
     * Returns the user's full name from LDAP.
     *
     * @param string $username Username
     */
    public function getCompleteName(string $username): bool|string
    {
        return $this->getLdapData($username, data: 'name');
    }

    /**
     * Returns the user's AD group memberships.
     *
     * @param string $username Username
     * @return array<string>|false Array of group DNs or false on error
     */
    public function getGroupMemberships(string $username): array|false
    {
        $connection = $this->ds;
        if (!$connection instanceof Connection) {
            $this->error = 'The LDAP connection handler is not a valid resource.';
            return false;
        }

        $base = $this->base;
        if ($base === null || $base === '') {
            $this->error = 'LDAP base DN is not configured.';
            return false;
        }

        $comparison = '(%s=%s)';
        $filter = sprintf(
            $comparison,
            (string) $this->configuration->get(item: 'ldap.ldap_mapping.username'),
            $this->quote($username),
        );

        $fields = ['memberOf'];

        $searchResult = @ldap_search($connection, $base, $filter, $fields);

        if (!$searchResult || is_array($searchResult)) {
            $errorMessage = 'Unable to search for "%s" (Error: %s)';
            $this->error = sprintf($errorMessage, $username, ldap_error($connection));

            return false;
        }

        $entryId = ldap_first_entry($connection, $searchResult);

        if (!$entryId) {
            $this->errno = ldap_errno($connection);
            $this->error = sprintf('Cannot get the value(s). Error: %s', ldap_error($connection));

            return false;
        }

        $entries = ldap_get_entries($connection, $searchResult);
        $groups = [];
        if ($entries === false) {
            return $groups;
        }

        $memberOf = $entries[0]['memberof'] ?? null;
        if ((int) $entries['count'] > 0 && is_array($memberOf)) {
            $memberOfCount = (int) ($memberOf['count'] ?? 0);
            for ($i = 0; $i < $memberOfCount; $i++) {
                $groups[] = (string) $memberOf[$i];
            }
        }

        return $groups;
    }

    /**
     * Returns the LDAP error message of the last LDAP command.
     */
    public function error(?Connection $ds = null): string
    {
        $connection = $ds ?? $this->ds;
        if (!$connection instanceof Connection) {
            return 'The LDAP connection handler is not a valid resource.';
        }

        return ldap_error($connection);
    }
}
