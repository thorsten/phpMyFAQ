<?php

declare(strict_types=1);

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Configuration;
use phpMyFAQ\Encryption;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Bcrypt::class)]
#[UsesClass(Encryption::class)]
final class BcryptTest extends TestCase
{
    private Encryption $bcrypt;

    protected function setUp(): void
    {
        $this->bcrypt = Encryption::getInstance('bcrypt', $this->createStub(Configuration::class));
    }

    public function testFactoryResolvesTheBcryptType(): void
    {
        $this->assertInstanceOf(Bcrypt::class, $this->bcrypt);
        $this->assertSame('', $this->bcrypt->error());
    }

    public function testEncryptProducesAVerifiableBcryptHash(): void
    {
        $hash = $this->bcrypt->encrypt('correct horse battery staple');

        $this->assertSame(PASSWORD_BCRYPT, password_get_info($hash)['algo']);
        $this->assertTrue(password_verify('correct horse battery staple', $hash));
        $this->assertFalse(password_verify('wrong password', $hash));
    }

    public function testEncryptSaltsEveryHash(): void
    {
        $this->assertNotSame($this->bcrypt->encrypt('secret'), $this->bcrypt->encrypt('secret'));
    }

    public function testSaltFromLoginDoesNotInfluenceTheHash(): void
    {
        $hash = $this->bcrypt->setSalt('admin')->encrypt('secret');

        $this->assertTrue(password_verify('secret', $hash));
    }
}
