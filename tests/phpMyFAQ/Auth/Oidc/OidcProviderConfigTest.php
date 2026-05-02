<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcProviderConfig::class)]
#[UsesClass(OidcClientConfig::class)]
final class OidcProviderConfigTest extends TestCase
{
    public function testExposesConstructorArguments(): void
    {
        $client = new OidcClientConfig('client-id', 'secret', 'https://example.com/cb', ['openid']);

        $config = new OidcProviderConfig(
            provider: 'keycloak',
            enabled: true,
            discoveryUrl: 'https://issuer.example.com/.well-known/openid-configuration',
            client: $client,
            autoProvision: false,
            logoutRedirectUrl: 'https://example.com/logout',
        );

        $this->assertSame('keycloak', $config->provider);
        $this->assertTrue($config->enabled);
        $this->assertSame('https://issuer.example.com/.well-known/openid-configuration', $config->discoveryUrl);
        $this->assertSame($client, $config->client);
        $this->assertFalse($config->autoProvision);
        $this->assertSame('https://example.com/logout', $config->logoutRedirectUrl);
    }
}
