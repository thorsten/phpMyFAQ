<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class ApiKeyAuthenticatorTest extends TestCase
{
    private Configuration $configuration;
    private DatabaseDriver $db;

    protected function setUp(): void
    {
        parent::setUp();

        Database::setTablePrefix('');
        $this->configuration = $this->createMock(Configuration::class);
        $this->db = $this->createMock(DatabaseDriver::class);
        $this->configuration->method('getDb')->willReturn($this->db);
    }

    public function testAuthenticateReturnsFalseWithoutBearerHeader(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->configuration);
        $request = new Request();

        $this->db->expects($this->never())->method('query');

        $this->assertFalse($authenticator->authenticate($request));
        $this->assertNull($authenticator->getAuthenticatedUserId());
        $this->assertNull($authenticator->getAuthenticatedApiKey());
    }

    public function testAuthenticateReturnsFalseWhenScopesAreMissing(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->configuration);
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer pmf_abc123']);

        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db
            ->method('fetchObject')
            ->willReturn((object) [
                'id' => 42,
                'user_id' => 7,
                'api_key' => hash('sha256', 'pmf_abc123'),
                'name' => 'test key',
                'scopes' => '["faq.read"]',
                'expires_at' => null,
                'created' => '2026-01-01 00:00:00',
            ]);

        $this->assertFalse($authenticator->authenticate($request, ['faq.write']));
        $this->assertNull($authenticator->getAuthenticatedUserId());
    }

    public function testAuthenticateReturnsFalseWhenApiKeyIsExpired(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->configuration);
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer pmf_expired']);

        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db
            ->method('fetchObject')
            ->willReturn((object) [
                'id' => 11,
                'user_id' => 3,
                'api_key' => hash('sha256', 'pmf_expired'),
                'name' => 'expired',
                'scopes' => '["faq.read"]',
                'expires_at' => '2000-01-01 00:00:00',
                'created' => '1999-12-31 00:00:00',
            ]);

        $this->assertFalse($authenticator->authenticate($request, ['faq.read']));
        $this->assertNull($authenticator->getAuthenticatedApiKey());
    }

    public function testAuthenticateStoresAuthenticatedApiKeyAndUpdatesLastUsedAt(): void
    {
        $authenticator = new ApiKeyAuthenticator($this->configuration);
        $request = new Request(server: ['HTTP_AUTHORIZATION' => 'Bearer pmf_valid123']);

        $queries = [];
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);
        $this->db->method('now')->willReturn('NOW()');
        $this->db
            ->method('query')
            ->willReturnCallback(static function (string $query) use (&$queries): bool {
                $queries[] = $query;
                return true;
            });
        $this->db
            ->method('fetchObject')
            ->willReturn((object) [
                'id' => 100,
                'user_id' => 12,
                'api_key' => hash('sha256', 'pmf_valid123'),
                'name' => 'CI key',
                'scopes' => '["faq.read","faq.write"]',
                'last_used_at' => null,
                'expires_at' => '2099-01-01 00:00:00',
                'created' => '2026-01-01 00:00:00',
            ]);

        $this->assertTrue($authenticator->authenticate($request, ['faq.read']));
        $this->assertSame(12, $authenticator->getAuthenticatedUserId());
        $this->assertSame('CI key', $authenticator->getAuthenticatedApiKey()['name']);
        $this->assertContains('UPDATE faqapi_keys SET last_used_at = NOW() WHERE id = 100', $queries);
    }
}
