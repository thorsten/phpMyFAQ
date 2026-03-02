<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use DateTimeImmutable;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RefreshTokenEntity::class)]
class RefreshTokenEntityTest extends TestCase
{
    private RefreshTokenEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new RefreshTokenEntity();
    }

    public function testImplementsRefreshTokenEntityInterface(): void
    {
        $this->assertInstanceOf(RefreshTokenEntityInterface::class, $this->entity);
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('refresh-token-xyz');
        $this->assertSame('refresh-token-xyz', $this->entity->getIdentifier());
    }

    public function testSetAndGetAccessToken(): void
    {
        $accessToken = $this->createStub(AccessTokenEntityInterface::class);
        $this->entity->setAccessToken($accessToken);
        $this->assertSame($accessToken, $this->entity->getAccessToken());
    }

    public function testSetAndGetExpiryDateTime(): void
    {
        $expiry = new DateTimeImmutable('2026-12-31 23:59:59');
        $this->entity->setExpiryDateTime($expiry);
        $this->assertSame($expiry, $this->entity->getExpiryDateTime());
    }
}
