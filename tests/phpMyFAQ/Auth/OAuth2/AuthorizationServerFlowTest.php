<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Drives the authorization code and client credentials flows through the real league server
 * against a private copy of the SQLite test database, with the test key pair.
 */
#[CoversClass(AuthorizationServer::class)]
#[UsesNamespace('phpMyFAQ')]
final class AuthorizationServerFlowTest extends TestCase
{
    use TestDatabaseTrait;

    private const string CLIENT_ID = 'flow-test-client';

    private const string CLIENT_SECRET = 'flow-test-secret-1234';

    private const string REDIRECT_URI = 'https://client.example.org/callback';

    private Configuration $configuration;

    private string $privateKeyPath;

    protected function setUp(): void
    {
        $this->configuration = $this->createTestConfiguration();

        $privateKeyPath = tempnam(sys_get_temp_dir(), 'pmf-oauth-flow-');
        self::assertNotFalse($privateKeyPath);
        copy(PMF_TEST_DIR . '/fixtures/oauth2/private.key', $privateKeyPath);
        chmod($privateKeyPath, 0o600);
        $this->privateKeyPath = $privateKeyPath;

        $this->configuration->set('oauth2.enable', 'true');
        $this->configuration->set('oauth2.privateKeyPath', $privateKeyPath);
        $this->configuration->set('oauth2.encryptionKey', 'encryption-key-123456');
        $this->configuration->set('oauth2.accessTokenTTL', 'PT1H');
        $this->configuration->set('oauth2.refreshTokenTTL', 'P1M');
        $this->configuration->set('oauth2.authCodeTTL', 'PT10M');

        $db = $this->configuration->getDb();
        $db->query(sprintf(
            "INSERT INTO %sfaqoauth_clients (client_id, client_secret, name, redirect_uri, grants, is_confidential)"
            . " VALUES ('%s', '%s', 'Flow test client', '%s', 'authorization_code,client_credentials', 1)",
            Database::getTablePrefix(),
            self::CLIENT_ID,
            password_hash(self::CLIENT_SECRET, PASSWORD_DEFAULT),
            self::REDIRECT_URI,
        ));
    }

    protected function tearDown(): void
    {
        @unlink($this->privateKeyPath);
    }

    private function authorizeRequest(): Request
    {
        return Request::create('https://localhost/authorize', 'GET', [
            'response_type' => 'code',
            'client_id' => self::CLIENT_ID,
            'redirect_uri' => self::REDIRECT_URI,
            'state' => 'opaque-state',
        ]);
    }

    private function countRows(string $table): int
    {
        $db = $this->configuration->getDb();
        $row = $db->fetchArray($db->query(sprintf('SELECT COUNT(*) AS n FROM %s%s', Database::getTablePrefix(), $table)));

        return (int) ($row['n'] ?? 0);
    }

    public function testCompleteAuthorizationRedirectsBackWithACodeAndState(): void
    {
        $server = new AuthorizationServer($this->configuration);

        $result = $server->completeAuthorization($this->authorizeRequest(), '1', true);

        $this->assertSame(Response::HTTP_FOUND, $result['status']);
        $this->assertArrayHasKey('Location', $result['headers']);
        $this->assertStringStartsWith(self::REDIRECT_URI . '?code=', $result['headers']['Location']);
        $this->assertStringContainsString('state=opaque-state', $result['headers']['Location']);
        // A redirect has no JSON body; the placeholder keeps the array shape stable for callers.
        $this->assertSame('server_error', $result['body']['error']);
        $this->assertSame(1, $this->countRows('faqoauth_auth_codes'));
    }

    public function testCompleteAuthorizationReportsADeniedConsent(): void
    {
        $server = new AuthorizationServer($this->configuration);

        $result = $server->completeAuthorization($this->authorizeRequest(), '1', false);

        $this->assertSame('access_denied', $result['body']['error']);
        $this->assertGreaterThanOrEqual(Response::HTTP_UNAUTHORIZED, $result['status']);
        $this->assertSame(0, $this->countRows('faqoauth_auth_codes'));
    }

    public function testCompleteAuthorizationRequiresAnAuthenticatedUser(): void
    {
        $server = new AuthorizationServer($this->configuration);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('OAuth2 authorization failed: OAuth2 authorization requires an authenticated user id.');

        $server->completeAuthorization($this->authorizeRequest(), '', true);
    }

    public function testIssueTokenGrantsClientCredentials(): void
    {
        $server = new AuthorizationServer($this->configuration);

        $result = $server->issueToken(Request::create('https://localhost/token', 'POST', [
            'grant_type' => 'client_credentials',
            'client_id' => self::CLIENT_ID,
            'client_secret' => self::CLIENT_SECRET,
        ]));

        $this->assertSame(Response::HTTP_OK, $result['status'], json_encode($result['body']));
        $this->assertSame('Bearer', $result['body']['token_type']);
        $this->assertArrayHasKey('access_token', $result['body']);
        // The lifetime is computed from the expiry timestamp, so a second may tick away in between.
        $this->assertEqualsWithDelta(3600, $result['body']['expires_in'], 1);
        $this->assertSame('no-store', $result['headers']['cache-control'] ?? $result['headers']['Cache-Control'] ?? null);
        $this->assertSame(1, $this->countRows('faqoauth_access_tokens'));
    }

    public function testIssueTokenRejectsAWrongClientSecret(): void
    {
        $server = new AuthorizationServer($this->configuration);

        $result = $server->issueToken(Request::create('https://localhost/token', 'POST', [
            'grant_type' => 'client_credentials',
            'client_id' => self::CLIENT_ID,
            'client_secret' => 'wrong',
        ]));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $result['status']);
        $this->assertSame('invalid_client', $result['body']['error']);
        $this->assertSame(0, $this->countRows('faqoauth_access_tokens'));
    }
}
