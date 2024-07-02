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
 * @copyright 2006-2024 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-01-04
 */

namespace phpMyFAQ;

use Elastic\Elasticsearch\Client;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Database\DatabaseDriver;

/**
 * Class Configuration
 *
 * @package phpMyFAQ
 */
class Configuration
{
    private array $config = [];

    private Logger $logger;

    private static ?Configuration $configuration = null;

    protected string $tableName = 'faqconfig';

    /**
     * Constructor.
     */
    public function __construct(DatabaseDriver $databaseDriver)
    {
        $this->setDatabase($databaseDriver);
        $this->setLogger();

        if (is_null(self::$configuration)) {
            self::$configuration = $this;
        }
    }

    public static function getConfigurationInstance(): Configuration
    {
        return self::$configuration;
    }

    public function setDatabase(DatabaseDriver $databaseDriver): void
    {
        $this->config['core.database'] = $databaseDriver;
    }

    /**
     * Sets the Monolog logger instance, logs into a normal logfile
     * If DEBUG is true, it logs to the browser console as well.
     */
    public function setLogger(): void
    {
        $this->logger = new Logger('phpmyfaq');
        $this->logger->pushHandler(new StreamHandler(PMF_LOG_DIR, DEBUG ? Level::Debug : Level::Warning));
        $this->logger->pushHandler(new BrowserConsoleHandler());
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    /**
     * Sets one single configuration item.
     */
    public function set(string $key, mixed $value): bool
    {
        $query = sprintf(
            "UPDATE %s%s SET config_value = '%s' WHERE config_name = '%s'",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim((string) $value)),
            $this->getDb()->escape(trim($key))
        );

        return (bool) $this->getDb()->query($query);
    }

    /**
     * Returns the DatabaseDriver object.
     */
    public function getDb(): DatabaseDriver
    {
        return $this->config['core.database'];
    }

    /**
     * Sets the Instance object.
     */
    public function setInstance(Instance $instance): void
    {
        $this->config['core.instance'] = $instance;
    }

    /**
     * Returns the Instance object.
     */
    public function getInstance(): Instance
    {
        return $this->config['core.instance'];
    }

    /**
     * Sets the Language object.
     */
    public function setLanguage(Language $language): void
    {
        $this->config['core.language'] = $language;
    }

    /**
     * Returns the Language object.
     */
    public function getLanguage(): Language
    {
        return $this->config['core.language'];
    }

    /**
     * Returns the default language.
     */
    public function getDefaultLanguage(): string
    {
        return str_replace(['language_', '.php'], '', (string) $this->config['main.language']);
    }

    /**
     * Returns the current version
     */
    public function getVersion(): string
    {
        return $this->config['main.currentVersion'];
    }

    /**
     * Returns the title of the FAQ installation
     */
    public function getTitle(): string
    {
        return $this->config['main.titleFAQ'];
    }

    /**
     * Returns the email address of the main admin
     */
    public function getAdminEmail(): string
    {
        return $this->config['main.administrationMail'];
    }

    /**
     * Returns the email address of the no-reply sender
     */
    public function getNoReplyEmail(): string
    {
        if (empty($this->config['mail.noReplySenderAddress'])) {
            return $this->getAdminEmail();
        }

        return $this->config['mail.noReplySenderAddress'];
    }

    /**
     * Returns the default URL of the phpMyFAQ installation.
     */
    public function getDefaultUrl(): string
    {
        $defaultUrl = $this->get('main.referenceURL');

        if (!str_ends_with((string) $defaultUrl, '/')) {
            return $defaultUrl . '/';
        }
        return $defaultUrl;
    }

    /**
     * Returns a configuration item.
     *
     * @param string $item Configuration item
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
     * Fetches and returns all configuration items into an array.
     */
    public function getAll(): array
    {
        $query = sprintf(
            'SELECT config_name, config_value FROM %s%s',
            Database::getTablePrefix(),
            $this->tableName
        );

        $result = $this->getDb()->query($query);
        $config = $this->getDb()->fetchAll($result);
        foreach ($config as $items) {
            $this->config[$items->config_name] = $items->config_value;
        }

        return $this->config;
    }

    /**
     * Sets the LDAP configuration.
     */
    public function setLdapConfig(LdapConfiguration $ldapConfiguration): void
    {
        // Always add the main LDAP server
        $this->config['core.ldapServer'][0] = [
            'ldap_server' => $ldapConfiguration->getMainServer(),
            'ldap_port' => $ldapConfiguration->getMainPort(),
            'ldap_user' => $ldapConfiguration->getMainUser(),
            'ldap_password' => $ldapConfiguration->getMainPassword(),
            'ldap_base' => $ldapConfiguration->getMainBase(),
        ];

        // Add multiple LDAP servers if enabled
        if (true === $this->get('ldap.ldap_use_multiple_servers')) {
            $key = 1;
            while (true) {
                if (isset($ldapConfiguration->getServers()[$key])) {
                    $this->config['core.ldapServer'][$key] = $ldapConfiguration->getServers()[$key];
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
     * Returns the LDAP configuration.
     *
     * @return string[]
     */
    public function getLdapConfig(): array
    {
        return $this->config['core.ldapConfig'] ?? [];
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

    public function isLdapActive(): bool
    {
        return (bool) $this->get('ldap.ldapSupport');
    }

    public function isElasticsearchActive(): bool
    {
        return (bool) $this->get('search.enableElasticsearch');
    }

    public function isSignInWithMicrosoftActive(): bool
    {
        return $this->get('security.enableSignInWithMicrosoft');
    }

    /**
     * Sets the Elasticsearch client instance.
     */
    public function setElasticsearch(Client $esClient): void
    {
        $this->config['core.elasticsearch'] = $esClient;
    }

    /**
     * Returns the Elasticsearch client instance.
     */
    public function getElasticsearch(): Client
    {
        return $this->config['core.elasticsearch'];
    }

    /**
     * Sets the Elasticsearch configuration.
     */
    public function setElasticsearchConfig(ElasticsearchConfiguration $elasticsearchConfiguration): void
    {
        $this->config['core.elasticsearchConfig'] = $elasticsearchConfiguration;
    }

    /**
     * Returns the Elasticsearch configuration.
     */
    public function getElasticsearchConfig(): ElasticsearchConfiguration
    {
        return $this->config['core.elasticsearchConfig'];
    }

    /**
     * Adds a configuration item for the database.
     */
    public function add(string $name, mixed $value): bool
    {
        $insert = sprintf(
            "INSERT INTO %s%s VALUES ('%s', '%s')",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim($name)),
            $this->getDb()->escape(trim((string) $value))
        );

        return (bool) $this->getDb()->query($insert);
    }

    /**
     * Deletes a configuration item for the database.
     */
    public function delete(string $name): bool
    {
        $delete = sprintf(
            "DELETE FROM %s%s WHERE config_name = '%s'",
            Database::getTablePrefix(),
            $this->tableName,
            $this->getDb()->escape(trim($name))
        );

        return (bool) $this->getDb()->query($delete);
    }

    /**
     * Renames a configuration key for the database.
     */
    public function rename(string $currentKey, string $newKey): bool
    {
        $rename = sprintf(
            "UPDATE %s%s SET config_name = '%s' WHERE config_name = '%s'",
            Database::getTablePrefix(),
            $this->tableName,
            $newKey,
            $currentKey
        );

        return (bool)$this->getDb()->query($rename);
    }

    /**
     * Updates all configuration items.
     *
     * @param string[] $newConfigs Array with new configuration values
     */
    public function update(array $newConfigs): bool
    {
        $runtimeConfigs = [
            'core.database', // phpMyFAQ\Database\DatabaseDriver
            'core.instance', // Instance
            'core.language', // Language
            'core.ldapServer', // Ldap
            'core.ldapConfig', // $LDAP
            'core.elasticsearch', // Elasticsearch\Client
            'core.elasticsearchConfig' // $ES
        ];

        foreach ($newConfigs as $name => $value) {
            if ($name != 'main.phpMyFAQToken' && !in_array($name, $runtimeConfigs)) {
                $update = sprintf(
                    "UPDATE %s%s SET config_value = '%s' WHERE config_name = '%s'",
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

    /**
     * Updates main.referenceUrl in media objects in faqs.
     *
     * @param string $oldUrl Old main.referenceUrl
     * @param string $newUrl New main.referenceUrl
     * @return bool true|false
     */
    public function replaceMainReferenceUrl(string $oldUrl, string $newUrl): bool
    {
        $query = sprintf("SELECT content FROM %sfaqdata", Database::getTablePrefix());
        $response = $this->getDb()->query($query);
        $contentItems = $this->getDb()->fetchAll($response);
        $newContentItems = [];

        foreach ($contentItems as $item) {
            if (str_contains($item->content, $oldUrl)) {
                $newContentItems[] = str_replace($oldUrl, $newUrl, $item->content);
            } else {
                $newContentItems[] = $item->content;
            }
        }

        $count = 0;
        foreach ($newContentItems as $newItem) {
            $query = sprintf(
                "UPDATE %sfaqdata SET content='%s' WHERE content='%s'",
                Database::getTablePrefix(),
                $this->getDb()->escape($newItem),
                $this->getDb()->escape($contentItems[$count]->content)
            );
            $count++;
            $this->getDb()->query($query);
        }

        return true;
    }

    /**
     * Returns an array with allowed media hosts for records
     *
     * @return string[]
     */
    public function getAllowedMediaHosts(): array
    {
        return explode(',', $this->get('records.allowedMediaHosts'));
    }
}
