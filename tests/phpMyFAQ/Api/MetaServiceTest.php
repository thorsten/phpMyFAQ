<?php

declare(strict_types=1);

namespace phpMyFAQ\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Helper\LanguageHelper;
use phpMyFAQ\Language;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(MetaService::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(LanguageHelper::class)]
final class MetaServiceTest extends TestCase
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
            'main.currentVersion' => '4.2.0',
            'main.titleFAQ' => 'phpMyFAQ Test',
            'oauth2.enable' => 'true',
            'spam.enableCaptchaCode' => 'false',
            'search.enableElasticsearch' => 'true',
            'search.enableOpenSearch' => 'false',
            'security.ssoSupport' => 'true',
            'main.referenceURL' => 'https://localhost/',
        ]));

        $language = new Language($this->configuration, $this->createStub(Session::class));
        $language->setLanguageWithDetection('language_en.php');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $reflection = new ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $configProperty->setValue($this->configuration, $this->originalConfig);
        $this->dbHandle->close();
    }

    public function testGetPublicMetadataReturnsExpectedPayload(): void
    {
        $service = new MetaService($this->configuration, new OAuthDiscoveryService($this->configuration));
        $payload = $service->getPublicMetadata();

        $this->assertSame('4.2.0', $payload['version']);
        $this->assertSame('phpMyFAQ Test', $payload['title']);
        $this->assertSame('en', $payload['language']);
        $this->assertSame(LanguageHelper::getAvailableLanguages(), $payload['availableLanguages']);
        $this->assertTrue($payload['enabledFeatures']['api']);
        $this->assertTrue($payload['enabledFeatures']['oauth2']);
        $this->assertFalse($payload['enabledFeatures']['captcha']);
        $this->assertFalse($payload['enabledFeatures']['ldap']);
        $this->assertFalse($payload['enabledFeatures']['opensearch']);
        $this->assertFalse($payload['enabledFeatures']['signInWithMicrosoft']);
        $this->assertStringEndsWith('/assets/images/logo-transparent.svg', $payload['publicLogoUrl']);
        $this->assertTrue($payload['oauthDiscovery']['enabled']);
        $this->assertStringEndsWith('/api', $payload['oauthDiscovery']['issuer']);
        $this->assertStringEndsWith('/api/oauth/authorize', $payload['oauthDiscovery']['authorizationEndpoint']);
        $this->assertStringEndsWith('/api/oauth/token', $payload['oauthDiscovery']['tokenEndpoint']);
        $this->assertSame(
            ['authorization_code', 'client_credentials', 'refresh_token'],
            $payload['oauthDiscovery']['grantTypesSupported'],
        );
        $this->assertSame(['code'], $payload['oauthDiscovery']['responseTypesSupported']);
        $this->assertSame(
            ['client_secret_basic', 'client_secret_post', 'none'],
            $payload['oauthDiscovery']['tokenEndpointAuthMethodsSupported'],
        );
    }
}
