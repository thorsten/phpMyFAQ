<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Functional\ControllerWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\Attributes\UsesNamespace;

#[CoversClass(OAuthDiscoveryController::class)]
#[UsesNamespace('phpMyFAQ')]
#[UsesClass(\phpMyFAQ\Controller\AbstractController::class)]
final class OAuthDiscoveryControllerWebTest extends ControllerWebTestCase
{
    public function testWellKnownEndpointReturnsNotFoundWhenOauthIsDisabled(): void
    {
        $this->overrideConfigurationValues(['oauth2.enable' => false], 'public');

        $response = $this->requestPublic('GET', '/.well-known/oauth-authorization-server');

        self::assertResponseStatusCodeSame(404, $response);
    }

    public function testWellKnownEndpointReturnsDiscoveryDocument(): void
    {
        $this->overrideConfigurationValues([
            'oauth2.enable' => true,
            'main.referenceURL' => 'https://localhost/',
        ], 'public');

        $response = $this->requestPublic('GET', '/.well-known/oauth-authorization-server');
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertResponseIsSuccessful($response);
        self::assertSame('application/json', $response->headers->get('Content-Type'));
        self::assertStringEndsWith('/api', $payload['issuer']);
        self::assertStringEndsWith('/api/oauth/authorize', $payload['authorization_endpoint']);
        self::assertStringEndsWith('/api/oauth/token', $payload['token_endpoint']);
        self::assertSame(['code'], $payload['response_types_supported']);
        self::assertSame(
            ['client_secret_basic', 'client_secret_post', 'none'],
            $payload['token_endpoint_auth_methods_supported'],
        );
    }
}
