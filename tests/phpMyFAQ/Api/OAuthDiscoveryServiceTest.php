<?php

declare(strict_types=1);

namespace phpMyFAQ\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Configuration\ConfigurationRepository;
use phpMyFAQ\Configuration\LayoutSettings;
use phpMyFAQ\Configuration\LdapSettings;
use phpMyFAQ\Configuration\MailSettings;
use phpMyFAQ\Configuration\SearchSettings;
use phpMyFAQ\Configuration\SecuritySettings;
use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettingsResolver;
use phpMyFAQ\Configuration\Storage\DatabaseConfigurationStore;
use phpMyFAQ\Configuration\Storage\HybridConfigurationStore;
use phpMyFAQ\Configuration\UrlSettings;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Environment;
use phpMyFAQ\Plugin\PluginManager;
use phpMyFAQ\System;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(OAuthDiscoveryService::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(Configuration::class)]
#[UsesClass(ConfigurationRepository::class)]
#[UsesClass(LayoutSettings::class)]
#[UsesClass(LdapSettings::class)]
#[UsesClass(MailSettings::class)]
#[UsesClass(SearchSettings::class)]
#[UsesClass(SecuritySettings::class)]
#[UsesClass(ConfigurationStorageSettingsResolver::class)]
#[UsesClass(DatabaseConfigurationStore::class)]
#[UsesClass(HybridConfigurationStore::class)]
#[UsesClass(UrlSettings::class)]
#[UsesClass(Sqlite3::class)]
#[UsesClass(Environment::class)]
#[UsesClass(PluginManager::class)]
#[UsesClass(System::class)]
#[UsesClass(Translation::class)]
final class OAuthDiscoveryServiceTest extends TestCase
{
    private Sqlite3 $dbHandle;
    private Configuration $configuration;
    private array $originalConfig = [];

    protected function setUp(): void
    {
        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $reflection = new ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        $this->assertIsArray($config);
        $this->originalConfig = $config;

        $configProperty->setValue($this->configuration, array_merge($config, [
            'oauth2.enable' => 'true',
            'main.referenceURL' => 'https://localhost/',
        ]));
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($this->configuration, $this->originalConfig);
        $this->dbHandle->close();
    }

    public function testGetDiscoveryDocumentReturnsExpectedStandardPayload(): void
    {
        $service = new OAuthDiscoveryService($this->configuration);
        $document = $service->getDiscoveryDocument();

        $this->assertSame('https://localhost/api', $document['issuer']);
        $this->assertSame('https://localhost/api/oauth/authorize', $document['authorization_endpoint']);
        $this->assertSame('https://localhost/api/oauth/token', $document['token_endpoint']);
        $this->assertSame(
            ['authorization_code', 'client_credentials', 'refresh_token'],
            $document['grant_types_supported'],
        );
        $this->assertSame(['code'], $document['response_types_supported']);
        $this->assertSame(
            ['client_secret_basic', 'client_secret_post', 'none'],
            $document['token_endpoint_auth_methods_supported'],
        );
    }

    public function testGetMetaDiscoveryReturnsCamelCasePayloadWithEnabledFlag(): void
    {
        $service = new OAuthDiscoveryService($this->configuration);
        $document = $service->getMetaDiscovery();

        $this->assertTrue($document['enabled']);
        $this->assertSame('https://localhost/api', $document['issuer']);
        $this->assertSame('https://localhost/api/oauth/authorize', $document['authorizationEndpoint']);
        $this->assertSame('https://localhost/api/oauth/token', $document['tokenEndpoint']);
        $this->assertSame(
            ['client_secret_basic', 'client_secret_post', 'none'],
            $document['tokenEndpointAuthMethodsSupported'],
        );
    }
}
