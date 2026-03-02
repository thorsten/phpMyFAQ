<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use phpMyFAQ\Auth\OAuth2\Entity\ScopeEntity;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Database\DatabaseDriver;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeRepository::class)]
#[AllowMockObjectsWithoutExpectations]
#[UsesClass(Database::class)]
#[UsesClass(ScopeEntity::class)]
class ScopeRepositoryTest extends TestCase
{
    private DatabaseDriver&MockObject $db;
    private ScopeRepository $repository;

    protected function setUp(): void
    {
        Database::setTablePrefix('');

        $this->db = $this->createMock(DatabaseDriver::class);
        $this->db->method('escape')->willReturnCallback(
            static fn(string $value): string => $value
        );

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getDb')->willReturn($this->db);

        $this->repository = new ScopeRepository($configuration);
    }

    public function testImplementsScopeRepositoryInterface(): void
    {
        $this->assertInstanceOf(ScopeRepositoryInterface::class, $this->repository);
    }

    public function testGetScopeEntityByIdentifierReturnsScopeWhenFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn((object) ['scope_id' => 'read']);

        $scope = $this->repository->getScopeEntityByIdentifier('read');

        $this->assertInstanceOf(ScopeEntity::class, $scope);
        $this->assertSame('read', $scope->getIdentifier());
    }

    public function testGetScopeEntityByIdentifierReturnsNullWhenQueryFails(): void
    {
        $this->db->method('query')->willReturn(false);

        $this->assertNull($this->repository->getScopeEntityByIdentifier('unknown'));
    }

    public function testGetScopeEntityByIdentifierReturnsNullWhenRowNotFound(): void
    {
        $this->db->method('query')->willReturn(new \stdClass());
        $this->db->method('fetchObject')->willReturn(false);

        $this->assertNull($this->repository->getScopeEntityByIdentifier('missing'));
    }

    public function testFinalizeScopesReturnsScopesUnchanged(): void
    {
        $scope1 = $this->createStub(ScopeEntityInterface::class);
        $scope2 = $this->createStub(ScopeEntityInterface::class);
        $client = $this->createStub(ClientEntityInterface::class);

        $scopes = [$scope1, $scope2];
        $result = $this->repository->finalizeScopes($scopes, 'authorization_code', $client, 'user-1', 'code-1');

        $this->assertSame($scopes, $result);
    }

    public function testFinalizeScopesWithEmptyArray(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);

        $result = $this->repository->finalizeScopes([], 'client_credentials', $client);

        $this->assertSame([], $result);
    }
}
