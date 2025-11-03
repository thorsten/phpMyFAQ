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
 * @copyright 2006-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2006-01-04
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Elastic\Elasticsearch\Client;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Plugin\PluginManager;

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

    private PluginManager $pluginManager;

    private ConfigurationRepository $repository;

    private LdapSettings $ldapSettings;

    private MailSettings $mailSettings;

    private SearchSettings $searchSettings;

    public function __construct(DatabaseDriver $databaseDriver)
    {
        $this->setDatabase($databaseDriver);
        $this->setLogger();
        try {
            $this->setPluginManager();
        } catch (PluginException $pluginException) {
            $this->getLogger()->error($pluginException->getMessage());
        }

        $this->repository = new ConfigurationRepository($this);
        $this->ldapSettings = new LdapSettings($this);
        $this->mailSettings = new MailSettings($this);
        $this->searchSettings = new SearchSettings($this);

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
     */
    public function setLogger(): void
    {
        $this->logger = new Logger(name: 'phpmyfaq');
        $this->logger->pushHandler(
            new StreamHandler(PMF_LOG_DIR, Environment::isDebugMode() ? Level::Debug : Level::Warning),
        );
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
        return $this->repository->updateConfigValue($key, (string) $value);
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
        return str_replace(['language_', '.php'], replace: '', subject: (string) $this->config['main.language']);
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

    public function getTemplateSet(): string
    {
        return $this->config['layout.templateSet'] ?? 'default';
    }

    /**
     * Returns the email address of the no-reply sender
     */
    public function getNoReplyEmail(): string
    {
        return $this->mailSettings->getNoReplyEmail();
    }

    /**
     * Returns the default URL of the phpMyFAQ installation.
     */
    public function getDefaultUrl(): string
    {
        $defaultUrl = $this->get(item: 'main.referenceURL');

        if (!str_ends_with((string) $defaultUrl, needle: '/')) {
            return $defaultUrl . '/';
        }

        return $defaultUrl;
    }

    public function getRootPath(): string
    {
        return PMF_ROOT_DIR;
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
     *
     * @return string[]
     */
    public function getAll(): array
    {
        $rows = $this->repository->fetchAll();
        foreach ($rows as $items) {
            $this->config[$items->config_name] = $items->config_value;
        }

        return $this->config;
    }

    /**
     * Sets the LDAP configuration.
     */
    public function setLdapConfig(LdapConfiguration $ldapConfiguration): void
    {
        $this->config['core.ldapServer'] = $this->ldapSettings->buildServers($ldapConfiguration);
        $this->config['core.ldapConfig'] = $this->ldapSettings->buildConfig();
    }

    /**
     * Returns the LDAP mapping configuration.
     *
     * @return string[]
     */
    public function getLdapMapping(): array
    {
        return $this->ldapSettings->getLdapMapping();
    }

    /**
     * Returns the LDAP options configuration.
     *
     * @return string[]
     */
    public function getLdapOptions(): array
    {
        return $this->ldapSettings->getLdapOptions();
    }

    /**
     * Returns the LDAP group configuration.
     *
     * @return array<string, string|array>
     */
    public function getLdapGroupConfig(): array
    {
        return $this->ldapSettings->getLdapGroupConfig();
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
        return $this->ldapSettings->isActive();
    }

    public function isElasticsearchActive(): bool
    {
        return $this->searchSettings->isElasticsearchActive();
    }

    public function isSignInWithMicrosoftActive(): bool
    {
        return $this->get(item: 'security.enableSignInWithMicrosoft');
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

    public function setOpenSearch(\OpenSearch\Client $osClient): void
    {
        $this->config['core.opensearch'] = $osClient;
    }

    public function getOpenSearch(): \OpenSearch\Client
    {
        return $this->config['core.opensearch'];
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

    public function setOpenSearchConfig(OpenSearchConfiguration $openSearchConfiguration): void
    {
        $this->config['core.openSearchConfig'] = $openSearchConfiguration;
    }

    public function getOpenSearchConfig(): OpenSearchConfiguration
    {
        return $this->config['core.openSearchConfig'];
    }

    /**
     * Adds a configuration item for the database.
     */
    public function add(string $name, mixed $value): bool
    {
        if (!isset($this->config[$name])) {
            return $this->repository->insert($name, (string) $value);
        }

        return true;
    }

    /**
     * Deletes a configuration item for the database.
     */
    public function delete(string $name): bool
    {
        return $this->repository->delete($name);
    }

    /**
     * Renames a configuration key for the database.
     */
    public function rename(string $currentKey, string $newKey): bool
    {
        return $this->repository->renameKey($currentKey, $newKey);
    }

    /**
     * Updates all configuration items.
     *
     * @param string[] $newConfigs Array with new configuration values
     */
    public function update(array $newConfigs): bool
    {
        $runtimeConfigs = [
            'core.database',
            'core.instance',
            'core.language',
            'core.ldapServer',
            'core.ldapConfig',
            'core.elasticsearch',
            'core.opensearch',
            'core.elasticsearchConfig',
            'core.openSearchConfig',
            'core.pluginManager',
        ];

        foreach ($newConfigs as $name => $value) {
            if (
                !hash_equals((string) $name, user_string: 'main.phpMyFAQToken')
                && !in_array($name, $runtimeConfigs, strict: true)
            ) {
                $this->repository->updateConfigValue((string) $name, $value ?? '');
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
        $contentItems = $this->repository->getFaqDataContents();
        $newContentItems = [];

        foreach ($contentItems as $contentItem) {
            if (str_contains((string) $contentItem->content, $oldUrl)) {
                $newContentItems[] = str_replace($oldUrl, $newUrl, $contentItem->content);
                continue;
            }
            $newContentItems[] = $contentItem->content;
        }

        $count = 0;
        foreach ($newContentItems as $newContentItem) {
            $this->repository->updateFaqDataContent($contentItems[$count]->content, $newContentItem);
            $count++;
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
        return explode(
            separator: ',',
            string: trim((string) $this->get(item: 'records.allowedMediaHosts')),
        );
    }

    public function getCustomCss(): string
    {
        return $this->get(item: 'layout.customCss');
    }

    /**
     * @throws PluginException
     */
    public function setPluginManager(): Configuration
    {
        $this->pluginManager = new PluginManager();
        $this->pluginManager->loadPlugins();

        $this->config['core.pluginManager'] = $this->pluginManager;
        return $this;
    }

    public function getPluginManager(): PluginManager
    {
        return $this->config['core.pluginManager'];
    }

    public function triggerEvent(string $eventName, mixed $data = null): void
    {
        $this->pluginManager->triggerEvent($eventName, $data);
    }

    public function getPluginConfig(string $pluginName): array
    {
        return $this->pluginManager->getPluginConfig($pluginName);
    }
}
