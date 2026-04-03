<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\RefreshTokenEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefreshTokenRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
#[UsesClass(RefreshTokenEntity::class)]
class RefreshTokenRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private RefreshTokenRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new RefreshTokenRepository($configuration);
    }

    public function testImplementsRefreshTokenRepositoryInterface(): void
    {
        $this->assertInstanceOf(RefreshTokenRepositoryInterface::class, $this->repository);
    }

    public function testGetNewRefreshTokenReturnsRefreshTokenEntity(): void
    {
        $token = $this->repository->getNewRefreshToken();
        $this->assertInstanceOf(RefreshTokenEntity::class, $token);
    }

    public function testPersistNewRefreshTokenInsertsRow(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db->expects($this->once())->method('query')->willReturn(true);

        $accessToken = $this->createStub(AccessTokenEntityInterface::class);
        $accessToken->method('getIdentifier')->willReturn('access-token-1');

        $token = new RefreshTokenEntity();
        $token->setIdentifier('refresh-token-abc');
        $token->setAccessToken($accessToken);
        $token->setExpiryDateTime(new DateTimeImmutable('2026-12-31 23:59:59'));

        $this->repository->persistNewRefreshToken($token);
    }

    public function testPersistNewRefreshTokenThrowsOnDbFailure(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db->method('query')->willReturn(false);

        $accessToken = $this->createStub(AccessTokenEntityInterface::class);
        $accessToken->method('getIdentifier')->willReturn('access-token-1');

        $token = new RefreshTokenEntity();
        $token->setIdentifier('refresh-token-dup');
        $token->setAccessToken($accessToken);
        $token->setExpiryDateTime(new DateTimeImmutable('2026-12-31 23:59:59'));

        $this->expectException(UniqueTokenIdentifierConstraintViolationException::class);
        $this->repository->persistNewRefreshToken($token);
    }

    public function testRevokeRefreshTokenExecutesUpdate(): void
    {
        $this->db->expects($this->once())->method('query')->with($this->stringContains('UPDATE'));

        $this->repository->revokeRefreshToken('refresh-to-revoke');
    }

    public function testIsRefreshTokenRevokedReturnsTrueWhenRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 1]);

        $this->assertTrue($this->repository->isRefreshTokenRevoked('token-revoked'));
    }

    public function testIsRefreshTokenRevokedReturnsFalseWhenNotRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 0]);

        $this->assertFalse($this->repository->isRefreshTokenRevoked('token-valid'));
    }

    public function testIsRefreshTokenRevokedReturnsTrueWhenQueryFails(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertTrue($this->repository->isRefreshTokenRevoked('token-unknown'));
    }

    public function testIsRefreshTokenRevokedReturnsTrueWhenRowNotFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn(false);

        $this->assertTrue($this->repository->isRefreshTokenRevoked('token-missing'));
    }
}
