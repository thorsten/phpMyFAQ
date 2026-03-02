<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ScopeEntity::class)]
class ScopeEntityTest extends TestCase
{
    private ScopeEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new ScopeEntity();
    }

    public function testImplementsScopeEntityInterface(): void
    {
        $this->assertInstanceOf(ScopeEntityInterface::class, $this->entity);
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('read');
        $this->assertSame('read', $this->entity->getIdentifier());
    }

    public function testJsonSerializeReturnsIdentifier(): void
    {
        $this->entity->setIdentifier('write');
        $this->assertSame('write', $this->entity->jsonSerialize());
    }

    public function testJsonEncodeProducesExpectedOutput(): void
    {
        $this->entity->setIdentifier('admin');
        $this->assertSame('"admin"', json_encode($this->entity));
    }
}
