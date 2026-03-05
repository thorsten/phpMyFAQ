<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AccessTokenEntity::class)]
class AccessTokenEntityTest extends TestCase
{
    private AccessTokenEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new AccessTokenEntity();
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('access-token-123');
        $this->assertSame('access-token-123', $this->entity->getIdentifier());
    }

    public function testSetAndGetClient(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);
        $this->entity->setClient($client);
        $this->assertSame($client, $this->entity->getClient());
    }

    public function testSetAndGetExpiryDateTime(): void
    {
        $expiry = new DateTimeImmutable('2026-12-31 23:59:59');
        $this->entity->setExpiryDateTime($expiry);
        $this->assertSame($expiry, $this->entity->getExpiryDateTime());
    }

    public function testSetAndGetUserIdentifier(): void
    {
        $this->entity->setUserIdentifier('user-42');
        $this->assertSame('user-42', $this->entity->getUserIdentifier());
    }

    public function testGetUserIdentifierReturnsNullByDefault(): void
    {
        $this->assertNull($this->entity->getUserIdentifier());
    }

    public function testAddAndGetScopes(): void
    {
        $scope1 = $this->createStub(ScopeEntityInterface::class);
        $scope1->method('getIdentifier')->willReturn('read');

        $scope2 = $this->createStub(ScopeEntityInterface::class);
        $scope2->method('getIdentifier')->willReturn('write');

        $this->entity->addScope($scope1);
        $this->entity->addScope($scope2);

        $scopes = $this->entity->getScopes();
        $this->assertCount(2, $scopes);
    }

    public function testAddDuplicateScopeDoesNotDuplicate(): void
    {
        $scope = $this->createStub(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('read');

        $this->entity->addScope($scope);
        $this->entity->addScope($scope);

        $this->assertCount(1, $this->entity->getScopes());
    }

    public function testImplementsAccessTokenEntityInterface(): void
    {
        $this->assertInstanceOf(\League\OAuth2\Server\Entities\AccessTokenEntityInterface::class, $this->entity);
    }
}
