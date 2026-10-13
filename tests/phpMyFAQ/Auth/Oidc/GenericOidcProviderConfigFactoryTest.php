<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use InvalidArgumentException;
use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GenericOidcProviderConfigFactory::class)]
#[UsesClass(OidcProviderConfig::class)]
#[UsesClass(OidcClientConfig::class)]
final class GenericOidcProviderConfigFactoryTest extends TestCase
{
    public function testCreateBuildsConfigFromConfiguration(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oidc.enable',            'true'],
                ['oidc.discoveryUrl',      ' https://auth.example.com/application/o/faq/.well-known/openid-configuration '],
                ['oidc.clientId',          'pmf-web'],
                ['oidc.clientSecret',      'super-secret'],
                ['oidc.redirectUri',       'https://faq.example.com/auth/oidc/callback'],
                ['oidc.scopes',            'openid profile email groups'],
                ['oidc.autoProvision',     '1'],
                ['oidc.logoutRedirectUrl', 'https://faq.example.com/logout'],
            ]);

        $config = new GenericOidcProviderConfigFactory($configuration)->create();

        $this->assertSame('oidc', $config->provider);
        $this->assertTrue($config->enabled);
        $this->assertSame(
            'https://auth.example.com/application/o/faq/.well-known/openid-configuration',
            $config->discoveryUrl,
        );
        $this->assertSame('pmf-web', $config->client->clientId);
        $this->assertSame('super-secret', $config->client->clientSecret);
        $this->assertSame('https://faq.example.com/auth/oidc/callback', $config->client->redirectUri);
        $this->assertSame(['openid', 'profile', 'email', 'groups'], $config->client->scopes);
        $this->assertTrue($config->autoProvision);
        $this->assertSame('https://faq.example.com/logout', $config->logoutRedirectUrl);
    }

    public function testCreateFallsBackToDefaultRedirectUri(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDefaultUrl')->willReturn('https://faq.example.com/');
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oidc.enable',       'false'],
                ['oidc.discoveryUrl', ''],
                ['oidc.clientId',     ''],
                ['oidc.clientSecret', ''],
                ['oidc.redirectUri',  ''],
                ['oidc.scopes',       'openid'],
                ['oidc.autoProvision', 'false'],
                ['oidc.logoutRedirectUrl', ''],
            ]);

        $config = new GenericOidcProviderConfigFactory($configuration)->create();

        $this->assertFalse($config->enabled);
        $this->assertSame('https://faq.example.com/auth/oidc/callback', $config->client->redirectUri);
    }

    public function testCreateThrowsWhenEnabledWithoutDiscoveryUrl(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oidc.enable',       'true'],
                ['oidc.discoveryUrl', ''],
                ['oidc.clientId',     'pmf-web'],
                ['oidc.scopes',       'openid'],
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('discoveryUrl');

        new GenericOidcProviderConfigFactory($configuration)->create();
    }

    public function testCreateThrowsWhenEnabledWithoutClientId(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnMap([
                ['oidc.enable',       'true'],
                ['oidc.discoveryUrl', 'https://auth.example.com/.well-known/openid-configuration'],
                ['oidc.clientId',     ''],
                ['oidc.scopes',       'openid'],
            ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('clientId');

        new GenericOidcProviderConfigFactory($configuration)->create();
    }
}
