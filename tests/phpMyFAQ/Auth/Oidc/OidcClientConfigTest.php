<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(OidcClientConfig::class)]
final class OidcClientConfigTest extends TestCase
{
    public function testExposesConstructorArguments(): void
    {
        $config = new OidcClientConfig(
            clientId: 'client-1',
            clientSecret: 'secret',
            redirectUri: 'https://example.com/callback',
            scopes: ['openid', 'profile', 'email'],
        );

        $this->assertSame('client-1', $config->clientId);
        $this->assertSame('secret', $config->clientSecret);
        $this->assertSame('https://example.com/callback', $config->redirectUri);
        $this->assertSame(['openid', 'profile', 'email'], $config->scopes);
    }

    public function testGetScopesAsStringJoinsScopesWithSpace(): void
    {
        $config = new OidcClientConfig('id', 'secret', 'uri', ['openid', 'profile']);

        $this->assertSame('openid profile', $config->getScopesAsString());
    }

    public function testGetScopesAsStringReturnsEmptyForNoScopes(): void
    {
        $config = new OidcClientConfig('id', 'secret', 'uri', []);

        $this->assertSame('', $config->getScopesAsString());
    }
}
