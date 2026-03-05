<?php

/**
 * Configuration methods for the Configuration class
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
 * @since     2026-02-24
 */

declare(strict_types=1);

namespace phpMyFAQ;

use Elastic\Elasticsearch\Client;
use Monolog\Handler\BrowserConsoleHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Translation\TranslationProviderFactory;
use phpMyFAQ\Translation\TranslationProviderInterface;

trait ConfigurationMethodsTrait
{
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
        return $this->configurationRepository->updateConfigValue($key, (string) $value);
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
     * Sets the Service Container.
     */
    public function setContainer(mixed $container): void
    {
        $this->config['core.container'] = $container;
    }

    /**
     * Returns the default language.
     */
    public function getDefaultLanguage(): string
    {
        if (!array_key_exists('main.language', $this->config) || $this->config['main.language'] === null) {
            return 'en';
        }

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
        return $this->layoutSettings->getTemplateSet();
    }

    /**
     * Returns the email address of the no-reply sender
     */
    public function getNoReplyEmail(): string
    {
        return $this->mailSettings->getNoReplyEmail();
    }

    public function getMailProvider(): string
    {
        return $this->mailSettings->getProvider();
    }

    /**
     * Returns the default URL of the phpMyFAQ installation.
     */
    public function getDefaultUrl(): string
    {
        return $this->urlSettings->getDefaultUrl();
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
        if (!array_key_exists($item, $this->config) || $this->config[$item] === null) {
            $this->getAll();
        }

        if (array_key_exists($item, $this->config) && $this->config[$item] !== null) {
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
        $rows = $this->configurationRepository->fetchAll();
        foreach ($rows as $row) {
            $this->config[$row->config_name] = $row->config_value;
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
        return $this->securitySettings->isSignInWithMicrosoftActive();
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
     * Sets the Translation Provider instance.
     */
    public function setTranslationProvider(TranslationProviderInterface $provider): void
    {
        $this->config['core.translationProvider'] = $provider;
    }

    /**
     * Returns the Translation Provider instance.
     */
    public function getTranslationProvider(): ?TranslationProviderInterface
    {
        // Lazy initialization: If not set yet and configuration exists, try to initialize
        if (
            (
                !array_key_exists('core.translationProvider', $this->config)
                || $this->config['core.translationProvider'] === null
            )
            && $this->get('translation.provider') !== 'none'
        ) {
            $this->initializeTranslationProvider();
        }

        return $this->config['core.translationProvider'] ?? null;
    }

    /**
     * Initialize the translation provider based on configuration.
     */
    private function initializeTranslationProvider(): void
    {
        try {
            // Get HTTP client from service container if available
            $container = $this->config['core.container'] ?? null;
            if ($container && $container->has('phpmyfaq.http-client')) {
                $httpClient = $container->get('phpmyfaq.http-client');
                $provider = TranslationProviderFactory::create($this, $httpClient);
                if ($provider !== null) {
                    $this->config['core.translationProvider'] = $provider;
                }
            }
        } catch (\Exception $e) {
            $this->getLogger()->error('Failed to initialize translation provider: ' . $e->getMessage());
        }
    }

    /**
     * Adds a configuration item for the database.
     */
    public function add(string $name, mixed $value): bool
    {
        if (!array_key_exists($name, $this->config) || $this->config[$name] === null) {
            return $this->configurationRepository->insert($name, (string) $value);
        }

        return true;
    }

    /**
     * Deletes a configuration item for the database.
     */
    public function delete(string $name): bool
    {
        return $this->configurationRepository->delete($name);
    }

    /**
     * Renames a configuration key for the database.
     */
    public function rename(string $currentKey, string $newKey): bool
    {
        return $this->configurationRepository->renameKey($currentKey, $newKey);
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
            'core.translationProvider',
            'core.pluginManager',
            'core.container',
        ];

        foreach ($newConfigs as $name => $value) {
            if (
                !(
                    !hash_equals((string) $name, user_string: 'main.phpMyFAQToken')
                    && !in_array($name, $runtimeConfigs, strict: true)
                )
            ) {
                continue;
            }

            $this->configurationRepository->updateConfigValue((string) $name, $value ?? '');
            if (array_key_exists($name, $this->config) && $this->config[$name] !== null) {
                unset($this->config[$name]);
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
        $contentItems = $this->configurationRepository->getFaqDataContents();

        foreach ($contentItems as $contentItem) {
            if (!str_contains((string) $contentItem->content, $oldUrl)) {
                continue;
            }

            $newContent = str_replace($oldUrl, $newUrl, $contentItem->content);
            $this->configurationRepository->updateFaqDataContentById(
                (int) $contentItem->id,
                (string) $contentItem->lang,
                $newContent,
            );
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
        return $this->urlSettings->getAllowedMediaHosts();
    }

    public function getCustomCss(): string
    {
        return $this->layoutSettings->getCustomCss();
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

    public function getPluginConfig(string $pluginName): ?PluginConfigurationInterface
    {
        return $this->pluginManager->getPluginConfig($pluginName);
    }
}
