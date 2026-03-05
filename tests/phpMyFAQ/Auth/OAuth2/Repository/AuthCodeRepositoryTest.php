<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\AuthCodeEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthCodeRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
class AuthCodeRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private AuthCodeRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(static fn(string $value): string => $value);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new AuthCodeRepository($configuration);
    }

    public function testImplementsAuthCodeRepositoryInterface(): void
    {
        $this->assertInstanceOf(AuthCodeRepositoryInterface::class, $this->repository);
    }

    public function testGetNewAuthCodeReturnsAuthCodeEntity(): void
    {
        $authCode = $this->repository->getNewAuthCode();
        $this->assertInstanceOf(AuthCodeEntity::class, $authCode);
    }

    public function testPersistNewAuthCodeInsertsRow(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $authCode = $this->createAuthCodeEntity('code-abc', 'client-1', 'user-1', 'https://example.com/cb');

        $this->repository->persistNewAuthCode($authCode);
    }

    public function testPersistNewAuthCodeWithNullUserAndRedirectUri(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db
            ->expects($this->once())
            ->method('query')
            ->willReturn(true);

        $authCode = $this->createAuthCodeEntity('code-xyz', 'client-1', null, null);

        $this->repository->persistNewAuthCode($authCode);
    }

    public function testPersistNewAuthCodeThrowsOnDbFailure(): void
    {
        $this->db->method('now')->willReturn('NOW()');
        $this->db->method('query')->willReturn(false);

        $authCode = $this->createAuthCodeEntity('code-dup', 'client-1', 'user-1', null);

        $this->expectException(UniqueTokenIdentifierConstraintViolationException::class);
        $this->repository->persistNewAuthCode($authCode);
    }

    public function testRevokeAuthCodeExecutesUpdate(): void
    {
        $this->db
            ->expects($this->once())
            ->method('query')
            ->with($this->stringContains('UPDATE'));

        $this->repository->revokeAuthCode('code-to-revoke');
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 1]);

        $this->assertTrue($this->repository->isAuthCodeRevoked('code-revoked'));
    }

    public function testIsAuthCodeRevokedReturnsFalseWhenNotRevoked(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['revoked' => 0]);

        $this->assertFalse($this->repository->isAuthCodeRevoked('code-valid'));
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenQueryFails(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertTrue($this->repository->isAuthCodeRevoked('code-unknown'));
    }

    public function testIsAuthCodeRevokedReturnsTrueWhenRowNotFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn(false);

        $this->assertTrue($this->repository->isAuthCodeRevoked('code-missing'));
    }

    private function createAuthCodeEntity(
        string $identifier,
        string $clientId,
        ?string $userId,
        ?string $redirectUri,
    ): AuthCodeEntity {
        $client = $this->createStub(ClientEntityInterface::class);
        $client->method('getIdentifier')->willReturn($clientId);

        $authCode = new AuthCodeEntity();
        $authCode->setIdentifier($identifier);
        $authCode->setClient($client);
        $authCode->setExpiryDateTime(new DateTimeImmutable('2026-12-31 23:59:59'));

        if ($userId !== null) {
            $authCode->setUserIdentifier($userId);
        }

        if ($redirectUri !== null) {
            $authCode->setRedirectUri($redirectUri);
        }

        return $authCode;
    }
}
