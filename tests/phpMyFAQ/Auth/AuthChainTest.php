<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class AuthChainTest extends TestCase
{
    public function testAuthenticateUsesSessionFirst(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $configuration = $this->createMock(Configuration::class);
        $db = $this->createMock(DatabaseDriver::class);
        $configuration->method('getDb')->willReturn($db);
        $apiKeyAuthenticator = new ApiKeyAuthenticator($configuration);

        Database::setTablePrefix('');
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(11);
        $db->expects($this->never())->method('query');

        $chain = new AuthChain($currentUser, $apiKeyAuthenticator);

        $this->assertTrue($chain->authenticate(new Request()));
        $this->assertSame(11, $chain->getAuthenticatedUserId());
        $this->assertSame('session', $chain->getAuthSource());
    }

    public function testAuthenticateFallsBackToApiKey(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $configuration = $this->createMock(Configuration::class);
        $db = $this->createMock(DatabaseDriver::class);
        $configuration->method('getDb')->willReturn($db);
        $apiKeyAuthenticator = new ApiKeyAuthenticator($configuration);

        Database::setTablePrefix('');
        $currentUser->method('isLoggedIn')->willReturn(false);
        $db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $db->method('now')->willReturn('NOW()');
        $db->method('query')->willReturn(true);
        $db->method('fetchObject')->willReturn((object) [
            'id' => 50,
            'user_id' => 22,
            'api_key' => 'pmf_fallback',
            'name' => 'fallback',
            'scopes' => '["faq.read"]',
            'expires_at' => '2099-01-01 00:00:00',
            'created' => '2026-01-01 00:00:00',
        ]);

        $chain = new AuthChain($currentUser, $apiKeyAuthenticator);

        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer pmf_fallback']);

        $this->assertTrue($chain->authenticate($request, ['faq.read']));
        $this->assertSame(22, $chain->getAuthenticatedUserId());
        $this->assertSame('api_key', $chain->getAuthSource());
    }

    public function testAuthenticateFallsBackToOAuth2(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $configuration = $this->createMock(Configuration::class);
        $db = $this->createMock(DatabaseDriver::class);
        $configuration->method('getDb')->willReturn($db);
        $apiKeyAuthenticator = new ApiKeyAuthenticator($configuration);

        Database::setTablePrefix('');
        $currentUser->method('isLoggedIn')->willReturn(false);

        $chain = new AuthChain($currentUser, $apiKeyAuthenticator);
        $chain->setOAuth2Authenticator(static fn(Request $request): int => 33);

        $this->assertTrue($chain->authenticate(new Request()));
        $this->assertSame(33, $chain->getAuthenticatedUserId());
        $this->assertSame('oauth2', $chain->getAuthSource());
    }

    public function testAuthenticateReturnsFalseWhenNoAuthenticatorSucceeds(): void
    {
        $currentUser = $this->createMock(CurrentUser::class);
        $configuration = $this->createMock(Configuration::class);
        $db = $this->createMock(DatabaseDriver::class);
        $configuration->method('getDb')->willReturn($db);
        $apiKeyAuthenticator = new ApiKeyAuthenticator($configuration);

        Database::setTablePrefix('');
        $currentUser->method('isLoggedIn')->willReturn(false);

        $chain = new AuthChain($currentUser, $apiKeyAuthenticator);
        $chain->setOAuth2Authenticator(static fn(Request $request): ?int => null);

        $this->assertFalse($chain->authenticate(new Request()));
        $this->assertNull($chain->getAuthenticatedUserId());
        $this->assertNull($chain->getAuthSource());
    }
}
