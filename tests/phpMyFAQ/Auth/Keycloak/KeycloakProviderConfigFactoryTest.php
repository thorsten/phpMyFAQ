<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Keycloak;

use InvalidArgumentException;
use phpMyFAQ\Auth\Oidc\OidcClientConfig;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(KeycloakProviderConfigFactory::class)]
#[UsesClass(OidcProviderConfig::class)]
#[UsesClass(OidcClientConfig::class)]
final class KeycloakProviderConfigFactoryTest extends TestCase
{
    public function testCreateBuildsKeycloakConfigFromConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['keycloak.enable',            'true'],
                ['keycloak.baseUrl',           ' https://sso.example.com/ '],
                ['keycloak.realm',             'faq'],
                ['keycloak.clientId',          'pmf-web'],
                ['keycloak.clientSecret',      'super-secret'],
                ['keycloak.redirectUri',       'https://faq.example.com/auth/keycloak/callback'],
                ['keycloak.scopes',            'openid profile email roles'],
                ['keycloak.autoProvision',     '1'],
                ['keycloak.logoutRedirectUrl', 'https://faq.example.com/logout'],
            ]);

        $factory = new KeycloakProviderConfigFactory($configuration);
        $config = $factory->create();

        $this->assertSame('keycloak', $config->provider);
        $this->assertTrue($config->enabled);
        $this->assertSame('https://sso.example.com/realms/faq/.well-known/openid-configuration', $config->discoveryUrl);
        $this->assertSame('pmf-web', $config->client->clientId);
        $this->assertSame('super-secret', $config->client->clientSecret);
        $this->assertSame('https://faq.example.com/auth/keycloak/callback', $config->client->redirectUri);
        $this->assertSame(['openid', 'profile', 'email', 'roles'], $config->client->scopes);
        $this->assertSame('openid profile email roles', $config->client->getScopesAsString());
        $this->assertTrue($config->autoProvision);
        $this->assertSame('https://faq.example.com/logout', $config->logoutRedirectUrl);
    }

    public function testCreateFallsBackToDefaultRedirectUri(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['keycloak.enable',            'false'],
                ['keycloak.baseUrl',           'https://sso.example.com'],
                ['keycloak.realm',             'faq'],
                ['keycloak.clientId',          ''],
                ['keycloak.clientSecret',      ''],
                ['keycloak.redirectUri',       ''],
                ['keycloak.scopes',            'openid profile email'],
                ['keycloak.autoProvision',     'false'],
                ['keycloak.logoutRedirectUrl', ''],
            ]);
        $configuration->method('getDefaultUrl')->willReturn('https://faq.example.com/');

        $factory = new KeycloakProviderConfigFactory($configuration);
        $config = $factory->create();

        $this->assertFalse($config->enabled);
        $this->assertSame('https://faq.example.com/auth/keycloak/callback', $config->client->redirectUri);
    }

    public function testCreateThrowsWhenEnabledButBaseUrlMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['keycloak.enable',            true],
                ['keycloak.baseUrl',           ''],
                ['keycloak.realm',             'faq'],
                ['keycloak.clientId',          ''],
                ['keycloak.clientSecret',      ''],
                ['keycloak.redirectUri',       'https://faq.example.com/cb'],
                ['keycloak.scopes',            ''],
                ['keycloak.autoProvision',     1],
                ['keycloak.logoutRedirectUrl', ''],
            ]);

        $factory = new KeycloakProviderConfigFactory($configuration);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Keycloak enabled but missing: baseUrl');

        $factory->create();
    }

    public function testCreateThrowsWhenEnabledButBothBaseUrlAndRealmMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['keycloak.enable',            true],
                ['keycloak.baseUrl',           ''],
                ['keycloak.realm',             ''],
                ['keycloak.clientId',          ''],
                ['keycloak.clientSecret',      ''],
                ['keycloak.redirectUri',       'https://faq.example.com/cb'],
                ['keycloak.scopes',            ''],
                ['keycloak.autoProvision',     false],
                ['keycloak.logoutRedirectUrl', ''],
            ]);

        $factory = new KeycloakProviderConfigFactory($configuration);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('baseUrl and realm');

        $factory->create();
    }

    public function testCreateAcceptsBooleanTrueAndIntegerOneForBooleans(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['keycloak.enable',            1],
                ['keycloak.baseUrl',           'https://sso.example.com'],
                ['keycloak.realm',             'faq'],
                ['keycloak.clientId',          'pmf-web'],
                ['keycloak.clientSecret',      'secret'],
                ['keycloak.redirectUri',       'https://faq.example.com/cb'],
                ['keycloak.scopes',            'openid'],
                ['keycloak.autoProvision',     true],
                ['keycloak.logoutRedirectUrl', ''],
            ]);

        $factory = new KeycloakProviderConfigFactory($configuration);
        $config = $factory->create();

        $this->assertTrue($config->enabled);
        $this->assertTrue($config->autoProvision);
    }
}
