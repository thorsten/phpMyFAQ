<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use League\OAuth2\Server\Entities\UserEntityInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(UserEntity::class)]
class UserEntityTest extends TestCase
{
    private UserEntity $entity;

    protected function setUp(): void
    {
        $this->entity = new UserEntity();
    }

    public function testImplementsUserEntityInterface(): void
    {
        $this->assertInstanceOf(UserEntityInterface::class, $this->entity);
    }

    public function testSetAndGetIdentifier(): void
    {
        $this->entity->setIdentifier('user-123');
        $this->assertSame('user-123', $this->entity->getIdentifier());
    }
}
