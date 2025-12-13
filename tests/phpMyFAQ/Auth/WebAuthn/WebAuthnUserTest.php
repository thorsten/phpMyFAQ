<?php

namespace phpMyFAQ\Auth\WebAuthn;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;

#[AllowMockObjectsWithoutExpectations]
class WebAuthnUserTest extends TestCase
{
    public function testSetAndGetId(): void
    {
        $user = new WebAuthnUser();
        $userId = 'user-id-123';

        $user->setId($userId);

        $this->assertSame($userId, $user->getId());
    }

    public function testSetAndGetName(): void
    {
        $user = new WebAuthnUser();
        $userName = 'John Doe';

        $user->setName($userName);

        $this->assertSame($userName, $user->getName());
    }

    public function testSetAndGetWebAuthnKeys(): void
    {
        $user = new WebAuthnUser();
        $webAuthnKeys = 'some-keys-data';

        $user->setWebAuthnKeys($webAuthnKeys);

        $this->assertSame($webAuthnKeys, $user->getWebAuthnKeys());
    }

    public function testFluentInterface(): void
    {
        $user = new WebAuthnUser();
        $result = $user->setId('id')->setName('name')->setWebAuthnKeys('keys');

        $this->assertInstanceOf(WebAuthnUser::class, $result);
    }

}
