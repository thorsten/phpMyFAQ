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
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\ElasticsearchConfiguration;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapConfiguration;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\OpenSearchConfiguration;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\Plugin\PluginConfigurationInterface;
use phpMyFAQ\Plugin\PluginException;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\Translation\TranslationProviderFactory;
use phpMyFAQ\Translation\TranslationProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/* @mago-expect lint:too-many-methods - legacy configuration facade; split into settings services is in progress */
trait ConfigurationMethodsTrait
{
    /**
     * Configuration store: string values from the faqconfig table plus the
     * runtime objects registered under `core.*` keys.
     *
     * @var array<string, mixed>
     */
    private array $config = [];

    private Logger $logger;

    private PluginManager $pluginManager;

    private ConfigurationRepository $configurationRepository;

    private LdapSettings $ldapSettings;

    /** @var array<string, mixed> LDAP runtime configuration built from ldap.php */
    private array $ldapConfig = [];

    /** @var array<int, array<string, mixed>> LDAP server definitions built from ldap.php */
    private array $ldapServer = [];

    private MailSettings $mailSettings;

    private SearchSettings $searchSettings;

    private SecuritySettings $securitySettings;

    private LayoutSettings $layoutSettings;

    private UrlSettings $urlSettings;

    /**
     * Returns the runtime object registered under the given `core.*` key,
     * checked against the expected class, or fails loudly when it was never
     * registered.
     *
     * @template T of object
     * @param class-string<T> $expectedClass
     * @return T
     */
    private function runtimeObject(string $key, string $expectedClass): object
    {
        /* @mago-expect analysis:mixed-assignment - the config store is mixed by design; validated below */
        $object = $this->config[$key] ?? null;
        if (!$object instanceof $expectedClass) {
            throw new \LogicException(sprintf('No %s registered under "%s".', $expectedClass, $key));
        }

        return $object;
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
        $result = $this->configurationRepository->updateConfigValue($key, (string) $value);
        if ($result) {
            $this->config[$key] = (string) $value;
        }

        return $result;
    }

    /**
     * Returns the DatabaseDriver object.
     */
    public function getDb(): DatabaseDriver
    {
        return $this->runtimeObject('core.database', DatabaseDriver::class);
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
        return $this->runtimeObject('core.instance', Instance::class);
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
        return $this->runtimeObject('core.language', Language::class);
    }

    /**
     * Sets the Service Container.
     */
    public function setContainer(mixed $container): void
    {
        $this->config['core.container'] = $container;
    }

    /**
     * Returns the registered service container, or null when none is set.
     */
    public function getServiceContainer(): ?\Psr\Container\ContainerInterface
    {
        $container = $this->config['core.container'] ?? null;

        return $container instanceof \Psr\Container\ContainerInterface ? $container : null;
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
        return (string) $this->config['main.currentVersion'];
    }

    /**
     * Returns the title of the FAQ installation
     */
    public function getTitle(): string
    {
        return (string) $this->config['main.titleFAQ'];
    }

    /**
     * Returns the email address of the main admin
     */
    public function getAdminEmail(): string
    {
        return (string) $this->config['main.administrationMail'];
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
        return (string) PMF_ROOT_DIR;
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
     * Fetches and returns all configuration items into an array. The result
     * also carries the runtime objects registered under `core.*` keys.
     *
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        $rows = $this->configurationRepository->fetchAll();
        foreach ($rows as $row) {
            $this->config[(string) $row->config_name] = $row->config_value === null
                ? null
                : (string) $row->config_value;
        }

        return $this->config;
    }

    /**
     * Sets the LDAP configuration.
     */
    public function setLdapConfig(LdapConfiguration $ldapConfiguration): void
    {
        $this->ldapServer = $this->ldapSettings->buildServers($ldapConfiguration);
        $this->ldapConfig = $this->ldapSettings->buildConfig();
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
     * @return array<string, mixed>
     */
    public function getLdapOptions(): array
    {
        return $this->ldapSettings->getLdapOptions();
    }

    /**
     * Returns the LDAP group configuration.
     *
     * @return array<string, mixed>
     */
    public function getLdapGroupConfig(): array
    {
        return $this->ldapSettings->getLdapGroupConfig();
    }

    /**
     * Returns the LDAP configuration.
     *
     * @return array<string, mixed>
     */
    public function getLdapConfig(): array
    {
        return $this->ldapConfig;
    }

    /**
     * Returns the LDAP server(s).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLdapServer(): array
    {
        return $this->ldapServer;
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

    public function isSignInWithKeycloakActive(): bool
    {
        return $this->securitySettings->isSignInWithKeycloakActive();
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
        return $this->runtimeObject('core.elasticsearch', Client::class);
    }

    public function setOpenSearch(\OpenSearch\Client $osClient): void
    {
        $this->config['core.opensearch'] = $osClient;
    }

    public function getOpenSearch(): \OpenSearch\Client
    {
        return $this->runtimeObject('core.opensearch', \OpenSearch\Client::class);
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
        return $this->runtimeObject('core.elasticsearchConfig', ElasticsearchConfiguration::class);
    }

    public function setOpenSearchConfig(OpenSearchConfiguration $openSearchConfiguration): void
    {
        $this->config['core.openSearchConfig'] = $openSearchConfiguration;
    }

    public function getOpenSearchConfig(): OpenSearchConfiguration
    {
        return $this->runtimeObject('core.openSearchConfig', OpenSearchConfiguration::class);
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

        /* @mago-expect analysis:mixed-assignment - the config store is mixed by design; validated below */
        $provider = $this->config['core.translationProvider'] ?? null;

        return $provider instanceof TranslationProviderInterface ? $provider : null;
    }

    /**
     * Initialize the translation provider based on configuration.
     */
    private function initializeTranslationProvider(): void
    {
        try {
            // Get HTTP client from service container if available
            /* @mago-expect analysis:mixed-assignment - the config store is mixed by design; validated below */
            $container = $this->config['core.container'] ?? null;
            if ($container instanceof ContainerInterface && $container->has('phpmyfaq.http-client')) {
                $httpClient = $container->get('phpmyfaq.http-client');
                if (!$httpClient instanceof HttpClientInterface) {
                    return;
                }

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
     * @param array<string, string|null> $newConfigs Array with new configuration values
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

        // Internal updater state that must never be writable through the bulk
        // configuration API. The legitimate updater writes this via set() after
        // verifying the downloaded package; allowing it here would let an admin
        // point the updater at an arbitrary, unverified ZIP (RCE).
        $protectedConfigs = [
            'upgrade.lastDownloadedPackage',
        ];

        foreach ($newConfigs as $name => $value) {
            if (
                !(
                    !hash_equals($name, user_string: 'main.phpMyFAQToken')
                    && !in_array($name, $runtimeConfigs, strict: true)
                    && !in_array($name, $protectedConfigs, strict: true)
                )
            ) {
                continue;
            }

            $this->configurationRepository->updateConfigValue($name, $value ?? '');
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
            $content = (string) $contentItem->content;
            if (!str_contains($content, $oldUrl)) {
                continue;
            }

            $this->configurationRepository->updateFaqDataContentById(
                (int) $contentItem->id,
                (string) $contentItem->lang,
                str_replace($oldUrl, $newUrl, $content),
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
        return $this->runtimeObject('core.pluginManager', PluginManager::class);
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
