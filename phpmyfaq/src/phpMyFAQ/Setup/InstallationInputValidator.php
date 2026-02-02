<?php

/**
 * Validates and parses installation input from POST data or a setup array.
 *
 * Extracts the ~280 lines of input parsing/validation from Installer::startInstall()
 * into a dedicated validator.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-01-31
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Filter;
use phpMyFAQ\System;

class InstallationInputValidator
{
    /**
     * Validates and parses installation input, returning a value object.
     *
     * @param array<string, mixed>|null $setup Optional setup array (for programmatic installs)
     * @throws Exception
     */
    public function validate(?array $setup = null): InstallationInput
    {
        $dbSetup = $this->validateDatabaseInput($setup);
        $ldapSetup = [];
        $esSetup = [];
        $osSetup = [];

        $ldapEnabled = $this->isLdapEnabled();
        if ($ldapEnabled) {
            $ldapSetup = $this->validateLdapInput();
        }

        $esEnabled = $this->isElasticsearchEnabled();
        if ($esEnabled) {
            $esSetup = $this->validateElasticsearchInput();
        }

        $osEnabled = $this->isOpenSearchEnabled();
        if ($osEnabled) {
            $osSetup = $this->validateOpenSearchInput();
        }

        [$loginName, $password] = $this->validateUserCredentials($setup);

        $language = Filter::filterInput(INPUT_POST, 'language', FILTER_SANITIZE_SPECIAL_CHARS, 'en');
        $realname = Filter::filterInput(INPUT_POST, 'realname', FILTER_SANITIZE_SPECIAL_CHARS, '');
        $email = Filter::filterInput(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL, '');
        $permLevel = Filter::filterInput(INPUT_POST, 'permLevel', FILTER_SANITIZE_SPECIAL_CHARS, 'basic');
        $rootDir = $setup['rootDir'] ?? PMF_ROOT_DIR;

        return new InstallationInput(
            dbSetup: $dbSetup,
            ldapSetup: $ldapSetup,
            esSetup: $esSetup,
            osSetup: $osSetup,
            loginName: (string) $loginName,
            password: (string) $password,
            language: (string) $language,
            realname: (string) $realname,
            email: (string) $email,
            permLevel: (string) $permLevel,
            rootDir: (string) $rootDir,
            ldapEnabled: $ldapEnabled,
            esEnabled: $esEnabled,
            osEnabled: $osEnabled,
        );
    }

    /**
     * @param array<string, mixed>|null $setup
     * @return array<string, string|int|null>
     * @throws Exception
     */
    private function validateDatabaseInput(?array $setup): array
    {
        $dbSetup = [];

        $dbSetup['dbPrefix'] = Filter::filterInput(INPUT_POST, 'sqltblpre', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if ('' !== $dbSetup['dbPrefix']) {
            Database::setTablePrefix($dbSetup['dbPrefix']);
        }

        if (!isset($setup['dbType'])) {
            $dbSetup['dbType'] = Filter::filterInput(INPUT_POST, 'sql_type', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $dbSetup['dbType'] = $setup['dbType'];
        }

        if (!is_null($dbSetup['dbType'])) {
            $dbSetup['dbType'] = trim((string) $dbSetup['dbType']);
            if (str_starts_with($dbSetup['dbType'], 'pdo_')) {
                $dataBaseFile = 'Pdo' . ucfirst(substr($dbSetup['dbType'], offset: 4));
            } else {
                $dataBaseFile = ucfirst($dbSetup['dbType']);
            }

            if (!file_exists(PMF_SRC_DIR . '/phpMyFAQ/Instance/Database/' . $dataBaseFile . '.php')) {
                throw new Exception(sprintf('Installation Error: Invalid server type "%s"', $dbSetup['dbType']));
            }
        } else {
            throw new Exception('Installation Error: Please select a database type.');
        }

        $dbSetup['dbServer'] = Filter::filterInput(INPUT_POST, 'sql_server', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbServer']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database server.');
        }

        if (!isset($setup['dbType'])) {
            $dbSetup['dbPort'] = Filter::filterInput(INPUT_POST, 'sql_port', FILTER_VALIDATE_INT);
        } else {
            $dbSetup['dbPort'] = $setup['dbPort'];
        }

        if (is_null($dbSetup['dbPort']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a valid database port.');
        }

        $dbSetup['dbUser'] = Filter::filterInput(INPUT_POST, 'sql_user', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbUser']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database username.');
        }

        $dbSetup['dbPassword'] = Filter::filterInput(INPUT_POST, 'sql_password', FILTER_SANITIZE_SPECIAL_CHARS, '');
        if (is_null($dbSetup['dbPassword']) && !System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbPassword'] = '';
        }

        if (!isset($setup['dbType'])) {
            $dbSetup['dbDatabaseName'] = Filter::filterInput(INPUT_POST, 'sql_db', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $dbSetup['dbDatabaseName'] = $setup['dbDatabaseName'];
        }

        if (is_null($dbSetup['dbDatabaseName']) && !System::isSqlite($dbSetup['dbType'])) {
            throw new Exception('Installation Error: Please add a database name.');
        }

        if (System::isSqlite($dbSetup['dbType'])) {
            $dbSetup['dbServer'] = Filter::filterInput(
                INPUT_POST,
                'sql_sqlitefile',
                FILTER_SANITIZE_SPECIAL_CHARS,
                $setup['dbServer'] ?? null,
            );
            if (is_null($dbSetup['dbServer'])) {
                throw new Exception('Installation Error: Please add a SQLite database filename.');
            }
        }

        return $dbSetup;
    }

    private function isLdapEnabled(): bool
    {
        $ldapEnabled = Filter::filterInput(INPUT_POST, 'ldap_enabled', FILTER_SANITIZE_SPECIAL_CHARS);
        return extension_loaded('ldap') && !is_null($ldapEnabled);
    }

    /**
     * @return array<string, string|int|null>
     * @throws Exception
     */
    private function validateLdapInput(): array
    {
        $ldapSetup = [];

        $ldapSetup['ldapServer'] = Filter::filterInput(INPUT_POST, 'ldap_server', FILTER_SANITIZE_SPECIAL_CHARS);
        if (is_null($ldapSetup['ldapServer'])) {
            throw new Exception('LDAP Installation Error: Please add a LDAP server.');
        }

        $ldapSetup['ldapPort'] = Filter::filterInput(INPUT_POST, 'ldap_port', FILTER_VALIDATE_INT);
        if (is_null($ldapSetup['ldapPort'])) {
            throw new Exception('LDAP Installation Error: Please add a LDAP port.');
        }

        $ldapSetup['ldapBase'] = Filter::filterInput(INPUT_POST, 'ldap_base', FILTER_SANITIZE_SPECIAL_CHARS);
        if (is_null($ldapSetup['ldapBase'])) {
            throw new Exception('LDAP Installation Error: Please add a LDAP base search DN.');
        }

        $ldapSetup['ldapUser'] = Filter::filterInput(INPUT_POST, 'ldap_user', FILTER_SANITIZE_SPECIAL_CHARS);
        $ldapSetup['ldapPassword'] = Filter::filterInput(INPUT_POST, 'ldap_password', FILTER_SANITIZE_SPECIAL_CHARS);

        return $ldapSetup;
    }

    private function isElasticsearchEnabled(): bool
    {
        return !is_null(Filter::filterInput(INPUT_POST, 'elasticsearch_enabled', FILTER_SANITIZE_SPECIAL_CHARS));
    }

    /**
     * @return array<string, string|array<string>>
     * @throws Exception
     */
    private function validateElasticsearchInput(): array
    {
        $esSetup = [];
        $esHostFilter = [
            'elasticsearch_server' => [
                'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags' => FILTER_REQUIRE_ARRAY,
            ],
        ];

        $esHosts = Filter::filterInputArray(INPUT_POST, $esHostFilter);
        if (is_null($esHosts)) {
            throw new Exception('Elasticsearch Installation Error: Please add at least one Elasticsearch host.');
        }

        $esSetup['hosts'] = $esHosts['elasticsearch_server'];

        $esSetup['index'] = Filter::filterInput(INPUT_POST, 'elasticsearch_index', FILTER_SANITIZE_SPECIAL_CHARS);
        if (is_null($esSetup['index'])) {
            throw new Exception('Elasticsearch Installation Error: Please add an Elasticsearch index name.');
        }

        return $esSetup;
    }

    private function isOpenSearchEnabled(): bool
    {
        return !is_null(Filter::filterInput(INPUT_POST, 'opensearch_enabled', FILTER_SANITIZE_SPECIAL_CHARS));
    }

    /**
     * @return array<string, string|array<string>>
     * @throws Exception
     */
    private function validateOpenSearchInput(): array
    {
        $osSetup = [];
        $osHostFilter = [
            'opensearch_server' => [
                'filter' => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags' => FILTER_REQUIRE_ARRAY,
            ],
        ];

        $osHosts = Filter::filterInputArray(INPUT_POST, $osHostFilter);
        if (is_null($osHosts)) {
            throw new Exception('OpenSearch Installation Error: Please add at least one OpenSearch host.');
        }

        $osSetup['hosts'] = $osHosts['opensearch_server'];

        $osSetup['index'] = Filter::filterInput(INPUT_POST, 'opensearch_index', FILTER_SANITIZE_SPECIAL_CHARS);
        if (is_null($osSetup['index'])) {
            throw new Exception('OpenSearch Installation Error: Please add an OpenSearch index name.');
        }

        return $osSetup;
    }

    /**
     * @param array<string, mixed>|null $setup
     * @return array{string, string}
     * @throws Exception
     */
    private function validateUserCredentials(?array $setup): array
    {
        if (!isset($setup['loginname'])) {
            $loginName = Filter::filterInput(INPUT_POST, 'loginname', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $loginName = $setup['loginname'];
        }

        if (is_null($loginName)) {
            throw new Exception('Installation Error: Please add a login name for your account.');
        }

        if (!isset($setup['password'])) {
            $password = Filter::filterInput(INPUT_POST, 'password', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $password = $setup['password'];
        }

        if (is_null($password)) {
            throw new Exception('Installation Error: Please add a password for your account.');
        }

        if (!isset($setup['password_retyped'])) {
            $passwordRetyped = Filter::filterInput(INPUT_POST, 'password_retyped', FILTER_SANITIZE_SPECIAL_CHARS);
        } else {
            $passwordRetyped = $setup['password_retyped'];
        }

        if (is_null($passwordRetyped)) {
            throw new Exception('Installation Error: Please add a retyped password.');
        }

        if (strlen((string) $password) <= 7 || strlen((string) $passwordRetyped) <= 7) {
            throw new Exception(
                'Installation Error: Your password and retyped password are too short. Please set your password '
                . 'and your retyped password with a minimum of 8 characters.',
            );
        }

        if (!hash_equals((string) $password, (string) $passwordRetyped)) {
            throw new Exception(
                'Installation Error: Your password and retyped password are not equal. Please check your password '
                . 'and your retyped password.',
            );
        }

        return [(string) $loginName, (string) $password];
    }
}
