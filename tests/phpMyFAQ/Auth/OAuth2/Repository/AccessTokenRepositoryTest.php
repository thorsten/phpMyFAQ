<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\AccessTokenEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessTokenRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
class AccessTokenRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private AccessTokenRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new AccessTokenRepository($configuration);
    }

    public function testImplementsAccessTokenRepositoryInterface(): void
    {
        $this->assertInstanceOf(AccessTokenRepositoryInterface::class, $this->repository);
    }

    public function testGetNewTokenReturnsAccessTokenEntity(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);
        $scope = $this->createStub(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('read');

        $token = $this->repository->getNewToken($client, [$scope], 'user-42');

        $this->assertInstanceOf(AccessTokenEntity::class, $token);
        $this->assertSame($client, $token->getClient());
        $this->assertSame('user-42', $token->getUserIdentifier());
        $this->assertCount(1, $token->getScopes());
    }

    public function testGetNewTokenWithoutUserIdentifier(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);

        $token = $this->repository->getNewToken($client, []);

        $this->assertNull($token->getUserIdentifier());
        $this->assertCount(0, $token->getScopes());
    }

    public function testGetNewTokenSkipsNonScopeEntries(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);
        $scope = $this->createStub(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('write');

        $token = $this->repository->getNewToken($client, ['invalid', $scope, 123]);

        $this->assertCount(1, $token->getScopes());
    }

    public function testPersistNewAccessTokenInsertsRow(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $token = $this->createTokenEntity('token-abc', 'client-1', 'user-1');

        $this->repository->persistNewAccessToken($token);
    }

    public function testPersistNewAccessTokenWithNullUserIdentifier(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $token = $this->createTokenEntity('token-xyz', 'client-1', null);

        $this->repository->persistNewAccessToken($token);
    }

    public function testPersistNewAccessTokenThrowsOnDuplicateKey(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db->method('query')->willReturn(false);
        $this->db->method('error')->willReturn('duplicate key violation');

        $token = $this->createTokenEntity('token-dup', 'client-1', 'user-1');

        $this->expectException(UniqueTokenIdentifierConstraintViolationException::class);
        $this->repository->persistNewAccessToken($token);
    }

    public function testPersistNewAccessTokenThrowsRuntimeExceptionOnOtherDbError(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db->method('query')->willReturn(false);
        $this->db->method('error')->willReturn('connection lost');

        $token = $this->createTokenEntity('token-err', 'client-1', 'user-1');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to persist access token');
        $this->repository->persistNewAccessToken($token);
    }

    public function testRevokeAccessTokenExecutesUpdate(): void
    {
        $this->db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE'));

        $this->repository->revokeAccessToken('token-to-revoke');
    }

    public function testIsAccessTokenRevokedReturnsTrueWhenRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 1]);

        $this->assertTrue($this->repository->isAccessTokenRevoked('token-revoked'));
    }

    public function testIsAccessTokenRevokedReturnsFalseWhenNotRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 0]);

        $this->assertFalse($this->repository->isAccessTokenRevoked('token-valid'));
    }

    public function testIsAccessTokenRevokedReturnsTrueWhenQueryFails(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertTrue($this->repository->isAccessTokenRevoked('token-unknown'));
    }

    public function testIsAccessTokenRevokedReturnsTrueWhenRowNotFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn(false);

        $this->assertTrue($this->repository->isAccessTokenRevoked('token-missing'));
    }

    private function createTokenEntity(
        string $identifier,
        string $clientId,
        ?string $userId,
    ): AccessTokenEntityInterface {
        $client = $this->createStub(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn($clientId);

        $token = new AccessTokenEntity();
        $token->setIdentifier($identifier);
        $token->setClient($client);
        $token->setExpiryDateTime(new DateTimeImmutable('2026-12-31 23:59:59'));

        if ($userId !== null) {
            $token->setUserIdentifier($userId);
        }

        return $token;
    }
}
