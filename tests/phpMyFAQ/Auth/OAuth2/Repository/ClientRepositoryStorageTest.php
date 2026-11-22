<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use phpMyFAQ\Auth\OAuth2\ClientRegistration;
use phpMyFAQ\Auth\OAuth2\Entity\ClientEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\OAuth2GrantType;
use phpMyFAQ\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;

/**
 * The methods the administration manages clients with, against a real database.
 */
#[CoversClass(ClientRepository::class)]
#[UsesNamespace('phpMyFAQ')]
final class ClientRepositoryStorageTest extends TestCase
{
    use TestDatabaseTrait;

    private Configuration $configuration;

    private ClientRepository $clients;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = $this->createTestConfiguration();
        $this->clients = new ClientRepository($this->configuration);
    }

    public function testRegisteredClientsAreListedByName(): void
    {
        $this->register('pmf_b', 'Reporting', [OAuth2GrantType::CLIENT_CREDENTIALS], confidential: true);
        $this->register('pmf_a', 'Intranet', [OAuth2GrantType::AUTHORIZATION_CODE], confidential: false);

        $clients = $this->clients->all();

        $this->assertSame(['Intranet', 'Reporting'], array_map(
            static fn(ClientEntity $client): string => $client->getName(),
            $clients,
        ));
        $this->assertSame('pmf_a', $clients[0]->getIdentifier());
        $this->assertSame('https://client.example.org/callback', $clients[0]->getRedirectUri());
        $this->assertSame(['authorization_code'], $clients[0]->allowedGrants);
        $this->assertFalse($clients[0]->isConfidential());
        $this->assertTrue($clients[1]->isConfidential());
    }

    public function testARegisteredConfidentialClientAuthenticatesWithItsSecretOnly(): void
    {
        $this->register('pmf_a', 'Reporting', [OAuth2GrantType::CLIENT_CREDENTIALS], confidential: true);

        $this->assertTrue($this->clients->validateClient('pmf_a', 'the-secret', 'client_credentials'));
        $this->assertFalse($this->clients->validateClient('pmf_a', 'another-secret', 'client_credentials'));
        $this->assertFalse($this->clients->validateClient('pmf_a', 'the-secret', 'authorization_code'));
    }

    public function testUpdateChangesNameRedirectUriAndGrantsButNotTheClientType(): void
    {
        $this->register('pmf_a', 'Reporting', [OAuth2GrantType::CLIENT_CREDENTIALS], confidential: true);

        $this->assertTrue($this->clients->update('pmf_a', new ClientRegistration(
            'Reporting v2',
            'https://reporting.example.org/callback',
            [OAuth2GrantType::AUTHORIZATION_CODE],
            confidential: false,
        )));

        $client = $this->clients->getClientEntity('pmf_a');
        $this->assertInstanceOf(ClientEntity::class, $client);
        $this->assertSame('Reporting v2', $client->getName());
        $this->assertSame('https://reporting.example.org/callback', $client->getRedirectUri());
        $this->assertSame(['authorization_code'], $client->allowedGrants);
        $this->assertTrue($client->isConfidential());
        $this->assertTrue($this->clients->validateClient('pmf_a', 'the-secret', 'authorization_code'));
    }

    public function testDeleteRemovesTheClientAndEverythingIssuedToIt(): void
    {
        $this->register('pmf_a', 'Reporting', [OAuth2GrantType::AUTHORIZATION_CODE], confidential: true);
        $this->register('pmf_b', 'Intranet', [OAuth2GrantType::AUTHORIZATION_CODE], confidential: true);
        $this->issueTokens('pmf_a', 'token-a');
        $this->issueTokens('pmf_b', 'token-b');

        $this->assertTrue($this->clients->delete('pmf_a'));

        $this->assertNull($this->clients->getClientEntity('pmf_a'));
        $this->assertInstanceOf(ClientEntity::class, $this->clients->getClientEntity('pmf_b'));

        // An access token without a stored row counts as revoked
        $accessTokens = new AccessTokenRepository($this->configuration);
        $this->assertTrue($accessTokens->isAccessTokenRevoked('token-a'));
        $this->assertFalse($accessTokens->isAccessTokenRevoked('token-b'));

        $this->assertSame(['refresh-token-b'], $this->identifiers('faqoauth_refresh_tokens'));
        $this->assertSame(['code-token-b'], $this->identifiers('faqoauth_auth_codes'));
    }

    /**
     * @param list<OAuth2GrantType> $grants
     */
    private function register(string $clientId, string $name, array $grants, bool $confidential): void
    {
        $registration = new ClientRegistration($name, 'https://client.example.org/callback', $grants, $confidential);
        $secretHash = $confidential ? password_hash('the-secret', PASSWORD_BCRYPT, ['cost' => 4]) : null;

        $this->assertTrue($this->clients->register($clientId, $registration, $secretHash, userId: 1));
    }

    private function issueTokens(string $clientId, string $tokenId): void
    {
        $db = $this->configuration->getDb();
        $db->query(sprintf(
            "INSERT INTO faqoauth_access_tokens (identifier, client_id, user_id, scopes, revoked, expires_at, created)
             VALUES ('%s', '%s', '1', '[]', 0, '2099-01-01 00:00:00', '2026-11-22 00:00:00')",
            $tokenId,
            $clientId,
        ));
        $db->query(sprintf(
            "INSERT INTO faqoauth_refresh_tokens (identifier, access_token_identifier, revoked, expires_at, created)
             VALUES ('refresh-%s', '%s', 0, '2099-01-01 00:00:00', '2026-11-22 00:00:00')",
            $tokenId,
            $tokenId,
        ));
        $db->query(sprintf(
            "INSERT INTO faqoauth_auth_codes (identifier, client_id, user_id, redirect_uri, scopes, revoked, expires_at, created)
             VALUES ('code-%s', '%s', '1', '', '[]', 0, '2099-01-01 00:00:00', '2026-11-22 00:00:00')",
            $tokenId,
            $clientId,
        ));
    }

    /**
     * @return list<string>
     */
    private function identifiers(string $table): array
    {
        $db = $this->configuration->getDb();
        $result = $db->query(sprintf('SELECT identifier FROM %s ORDER BY identifier', $table));

        return array_map(
            static fn(object $row): string => (string) $row->identifier,
            $db->fetchAll($result) ?? [],
        );
    }
}
