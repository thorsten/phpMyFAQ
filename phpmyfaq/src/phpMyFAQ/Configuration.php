<?php

/**
 * The main class for fetching the configuration, update and delete items. This
 * class is also a small Dependency Injection Container for phpMyFAQ.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-01-04
 */

namespace phpMyFAQ;

use Elasticsearch\Client;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class Configuration
 *
 * @package phpMyFAQ
 */
class Configuration
{
    /**
     * @var array
     */
    public array $config = [];

    /**
     * @var string
     */
    protected string $tableName = 'faqconfig';

    /**
     * Constructor.
     *
     * @param DatabaseDriver $database
     */
    public function __construct(DatabaseDriver $database)
    {
        $this->setDb($database);
    }

    /**
     * Sets the phpMyFAQ\Db_Driver object.
     *
     * @param DatabaseDriver $database
     */
    public function setDb(DatabaseDriver $database): void
    {
        $this->config['core.database'] = $database;
    }

    /**
     * Returns all sorting possibilities for FAQ records.
     *
     * @param string $current
     * @return string
     */
    public static function sortingOptions(string $current): string
    {
        $options = ['id', 'thema', 'visits', 'updated', 'author'];
        $output = '';

        foreach ($options as $value) {
            printf(
                '<option value="%s" %s>%s</option>',
                $value,
                ($value == $current) ? 'selected' : '',
                Translation::get('ad_conf_order_' . $value)
            );
        }

        return $output;
    }

    /**
     * Sets one single configuration item.
     *
     * @param string $key
     * @param mixed  $value
     * @return bool
     */
    public function set(string $key, mixed $value): bool
    {
        $query = sprintf(
            "UPDATE %s%s SET config_value = '%s' WHERE config_name = '%s'",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim($value)),
            $this->getDb()->escape(trim($key))
        );

        return (bool) $this->getDb()->query($query);
    }

    /**
     * Returns the DatabaseDriver object.
     *
     * @return DatabaseDriver
     */
    public function getDb(): DatabaseDriver
    {
        return $this->config['core.database'];
    }

    /**
     * Sets the Instance object.
     *
     * @param Instance $instance
     */
    public function setInstance(Instance $instance): void
    {
        $this->config['core.instance'] = $instance;
    }

    /**
     * Returns the Instance object.
     *
     * @return Instance
     */
    public function getInstance(): Instance
    {
        return $this->config['core.instance'];
    }

    /**
     * Sets the Language object.
     *
     * @param Language $language
     */
    public function setLanguage(Language $language): void
    {
        $this->config['core.language'] = $language;
    }

    /**
     * Returns the Language object.
     *
     * @return Language
     */
    public function getLanguage(): Language
    {
        return $this->config['core.language'];
    }

    /**
     * Returns the default language.
     * @return string
     */
    public function getDefaultLanguage(): string
    {
        return str_replace(['language_', '.php'], '', $this->config['main.language']);
    }

    /**
     * Returns the current version
     * @return string
     */
    public function getVersion(): string
    {
        return $this->config['main.currentVersion'];
    }

    /**
     * Returns the title of the FAQ installation
     * @return string
     */
    public function getTitle(): string
    {
        return $this->config['main.titleFAQ'];
    }

    /**
     * Returns the email address of the main admin
     * @return string
     */
    public function getAdminEmail(): string
    {
        return $this->config['main.administrationMail'];
    }

    /**
     * Returns the default URL of the phpMyFAQ installation.
     *
     * @return string
     */
    public function getDefaultUrl(): string
    {
        $defaultUrl = $this->get('main.referenceURL');

        if (!str_ends_with($defaultUrl, '/')) {
            return $defaultUrl . '/';
        } else {
            return $defaultUrl;
        }
    }

    /**
     * Returns a configuration item.
     *
     * @param string $item Configuration item
     * @return mixed
     */
    public function get(string $item): mixed
    {
        if (!isset($this->config[$item])) {
            $this->getAll();
        }

        if (isset($this->config[$item])) {
            return match ($this->config[$item]) {
                'true' => true,
                'false' => false,
                default => $this->config[$item],
            };
        }

        return null;
    }

    /**
     * Fetches all configuration items into an array.
     */
    public function getAll(): void
    {
        $query = sprintf(
            '
            SELECT
                config_name, config_value
            FROM
                %s%s',
            Database::getTablePrefix(),
            $this->tableName
        );

        $result = $this->getDb()->query($query);
        $config = $this->getDb()->fetchAll($result);
        foreach ($config as $items) {
            $this->config[$items->config_name] = $items->config_value;
        }
    }

    /**
     * Sets the LDAP configuration.
     *
     * @param string[] $ldapConfig
     */
    public function setLdapConfig(array $ldapConfig): void
    {
        // Always add main LDAP server
        $this->config['core.ldapServer'][0] = [
            'ldap_server' => $ldapConfig['ldap_server'],
            'ldap_port' => $ldapConfig['ldap_port'],
            'ldap_user' => $ldapConfig['ldap_user'],
            'ldap_password' => $ldapConfig['ldap_password'],
            'ldap_base' => $ldapConfig['ldap_base'],
        ];

        // Add multiple LDAP servers if enabled
        if (true === $this->get('ldap.ldap_use_multiple_servers')) {
            $key = 1;
            while ($key >= 1) {
                if (isset($ldapConfig[$key])) {
                    $this->config['core.ldapServer'][$key] = $ldapConfig[$key];
                    ++$key;
                } else {
                    break;
                }
            }
        }

        // Set LDAP configuration
        $this->config['core.ldapConfig'] = [
            'ldap_use_multiple_servers' => $this->get('ldap.ldap_use_multiple_servers'),
            'ldap_mapping' => $this->getLdapMapping(),
            'ldap_use_domain_prefix' => $this->get('ldap.ldap_use_domain_prefix'),
            'ldap_options' => $this->getLdapOptions(),
            'ldap_use_memberOf' => $this->get('ldap.ldap_use_memberOf'),
            'ldap_use_sasl' => $this->get('ldap.ldap_use_sasl'),
            'ldap_use_anonymous_login' => $this->get('ldap.ldap_use_anonymous_login'),
        ];
    }

    /**
     * Sets the Active Directory configuration.
     *
     * @param string[] $ldapConfig
     */
    public function setActiveDirectoryConfig(array $ldapConfig): void
    {
        // Always add main Active Directory server
        $this->config['core.activeDirectoryServer'][0] = [
            'ad_server' => $ldapConfig['ad_server'],
            'ad_port' => $ldapConfig['ad_port'],
            'ad_user' => $ldapConfig['ad_user'],
            'ad_password' => $ldapConfig['ad_password'],
            'ad_base' => $ldapConfig['ad_base'],
        ];

        // Add multiple Active Directory servers if enabled
        if (true === $this->get('ad.ad_use_multiple_servers')) {
            $key = 1;
            while ($key >= 1) {
                if (isset($ldapConfig[$key])) {
                    $this->config['core.activeDirectoryServer'][$key] = $ldapConfig[$key];
                    ++$key;
                } else {
                    break;
                }
            }
        }

        // Set LDAP configuration
        $this->config['core.ldapConfig'] = [
            'ad_use_multiple_servers' => $this->get('ad.ad_use_multiple_servers'),
            'ad_mapping' => $this->getActiveDirectoryMapping(),
            'ad_use_domain_prefix' => $this->get('ad.ad_use_domain_prefix'),
            'ad_options' => $this->getActiveDirectoryOptions(),
            'ad_use_memberOf' => $this->get('ad.ad_use_memberOf'),
            'ad_use_sasl' => $this->get('ad.ad_use_sasl'),
            'ad_use_anonymous_login' => $this->get('ad.ad_use_anonymous_login'),
        ];
    }

    /**
     * Returns the Active Directory mapping configuration.
     *
     * @return string[]
     */
    public function getActiveDirectoryMapping(): array
    {
        return [
            'name' => $this->get('ad.ad_mapping.name'),
            'username' => $this->get('ad.ad_mapping.username'),
            'mail' => $this->get('ad.ad_mapping.mail'),
            'memberOf' => $this->get('ad.ad_mapping.memberOf')
        ];
    }

    /**
     * Returns the LDAP mapping configuration.
     *
     * @return string[]
     */
    public function getLdapMapping(): array
    {
        return [
            'name' => $this->get('ldap.ldap_mapping.name'),
            'username' => $this->get('ldap.ldap_mapping.username'),
            'mail' => $this->get('ldap.ldap_mapping.mail'),
            'memberOf' => $this->get('ldap.ldap_mapping.memberOf')
        ];
    }

    /**
     * Returns the LDAP options configuration.
     *
     * @return string[]
     */
    public function getLdapOptions(): array
    {
        return [
            'LDAP_OPT_PROTOCOL_VERSION' => $this->get('ldap.ldap_options.LDAP_OPT_PROTOCOL_VERSION'),
            'LDAP_OPT_REFERRALS' => $this->get('ldap.ldap_options.LDAP_OPT_REFERRALS')
        ];
    }

    /**
     * Returns the Active Directory options configuration.
     *
     * @return string[]
     */
    public function getActiveDirectoryOptions(): array
    {
        return [
            'LDAP_OPT_PROTOCOL_VERSION' => $this->get('ad.ad_options.LDAP_OPT_PROTOCOL_VERSION'),
            'LDAP_OPT_REFERRALS' => $this->get('ad.ad_options.LDAP_OPT_REFERRALS')
        ];
    }

    /**
     * Returns the LDAP configuration.
     *
     * @return string[]
     */
    public function getLdapConfig(): array
    {
        return $this->config['core.ldapConfig'] ?? [];
    }

    /**
     * Returns the Active Directory configuration.
     *
     * @return string[]
     */
    public function getActiveDirectoryConfig(): array
    {
        return $this->config['core.activeDirectoryConfig'] ?? [];
    }

    /**
     * Returns the LDAP server(s).
     *
     * @return string[]
     */
    public function getLdapServer(): array
    {
        return $this->config['core.ldapServer'] ?? [];
    }

    /**
     * Returns the Active Directory server(s).
     *
     * @return string[]
     */
    public function getActiveDirectoryServer(): array
    {
        return $this->config['core.activeDirectoryServer'] ?? [];
    }

    /**
     * Sets the Elasticsearch client instance.
     *
     * @param Client $esClient
     */
    public function setElasticsearch(Client $esClient): void
    {
        $this->config['core.elasticsearch'] = $esClient;
    }

    /**
     * Returns the Elasticsearch client instance.
     *
     * @return Client
     */
    public function getElasticsearch(): Client
    {
        return $this->config['core.elasticsearch'];
    }

    /**
     * Sets the Elasticsearch configuration.
     *
     * @param string[] $data
     */
    public function setElasticsearchConfig(array $data): void
    {
        $this->config['core.elasticsearchConfig'] = $data;
    }

    /**
     * Returns the Elasticsearch configuration.
     *
     * @return string[]
     */
    public function getElasticsearchConfig(): array
    {
        return $this->config['core.elasticsearchConfig'] ?? [];
    }

    /**
     * Adds a configuration item for the database.
     *
     * @param string $name
     * @param mixed  $value
     * @return bool|object
     */
    public function add(string $name, mixed $value): object|bool
    {
        $insert = sprintf(
            "INSERT INTO
                %s%s
            VALUES
                ('%s', '%s')",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim($name)),
            $this->getDb()->escape(trim($value))
        );

        return $this->getDb()->query($insert);
    }

    /**
     * Deletes a configuration item for the database.
     *
     * @param string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        $delete = sprintf(
            "DELETE FROM
                %s%s
            WHERE
              config_name = '%s'",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim($name))
        );

        return (bool)$this->getDb()->query($delete);
    }

    /**
     * Updates all configuration items.
     *
     * @param string[] $newConfigs Array with new configuration values
     *
     * @return bool
     */
    public function update(array $newConfigs): bool
    {
        $runtimeConfigs = [
            'core.database', // phpMyFAQ\Database\DatabaseDriver
            'core.instance', // Instance
            'core.language', // Language
            'core.ldapServer', // Ldap
            'core.ldapConfig', // $LDAP
            'core.activeDirectoryServer', // Active Directory
            'core.activeDirectoryConfig', // $AD
            'core.elasticsearch', // Elasticsearch\Client
            'core.elasticsearchConfig' // $ES
        ];

        foreach ($newConfigs as $name => $value) {
            if (
                $name != 'main.phpMyFAQToken'
                && !in_array($name, $runtimeConfigs)
            ) {
                $update = sprintf(
                    "
                    UPDATE
                        %s%s
                    SET
                        config_value = '%s'
                    WHERE
                        config_name = '%s'",
                    Database::getTablePrefix(),
                    $this->tableName,
                    $this->getDb()->escape(trim($value)),
                    $name
                );

                $this->getDb()->query($update);
                if (isset($this->config[$name])) {
                    unset($this->config[$name]);
                }
            }
        }

        return true;
    }
}
