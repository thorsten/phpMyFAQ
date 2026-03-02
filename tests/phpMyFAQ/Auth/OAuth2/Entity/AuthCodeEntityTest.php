<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AuthCodeEntity::class)]
class AuthCodeEntityTest extends TestCase
{
    private AuthCodeEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new AuthCodeEntity();
    }

    public function testImplementsAuthCodeEntityInterface(): void
    {
        $this->assertInstanceOf(AuthCodeEntityInterface::class, $this->entity);
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('auth-code-abc');
        $this->assertSame('auth-code-abc', $this->entity->getIdentifier());
    }

    public function testSetAndGetRedirectUri(): void
    {
        $this->entity->setRedirectUri('https://example.com/callback');
        $this->assertSame('https://example.com/callback', $this->entity->getRedirectUri());
    }

    public function testGetRedirectUriReturnsNullByDefault(): void
    {
        $this->assertNull($this->entity->getRedirectUri());
    }

    public function testSetAndGetClient(): void
    {
        $client = $this->createStub(ClientEntityInterface::class);
        $this->entity->setClient($client);
        $this->assertSame($client, $this->entity->getClient());
    }

    public function testSetAndGetExpiryDateTime(): void
    {
        $expiry = new DateTimeImmutable('2026-06-15 12:00:00');
        $this->entity->setExpiryDateTime($expiry);
        $this->assertSame($expiry, $this->entity->getExpiryDateTime());
    }

    public function testSetAndGetUserIdentifier(): void
    {
        $this->entity->setUserIdentifier('user-99');
        $this->assertSame('user-99', $this->entity->getUserIdentifier());
    }

    public function testAddAndGetScopes(): void
    {
        $scope = $this->createStub(ScopeEntityInterface::class);
        $scope->method('getIdentifier')->willReturn('profile');

        $this->entity->addScope($scope);

        $scopes = $this->entity->getScopes();
        $this->assertCount(1, $scopes);
    }
}
