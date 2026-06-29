<?php

/**
 * Hash EncryptionTypes Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-06-27
 */

namespace phpMyFAQ\EncryptionTypes;

use phpMyFAQ\Configuration;
use phpMyFAQ\Encryption;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class HashTest
 */
#[CoversClass(Hash::class)]
#[CoversClass(Encryption::class)]
class HashTest extends TestCase
{
    private Configuration $config;

    private Encryption $hash;

    protected function setUp(): void
    {
        $this->config = $this->createStub(Configuration::class);
        $this->hash = Encryption::getInstance('hash', $this->config);
    }

    public function testGetInstanceReturnsHashObject(): void
    {
        $this->assertInstanceOf(Hash::class, $this->hash);
        $this->assertInstanceOf(Encryption::class, $this->hash);
    }

    public function testEncryptReturnsSha256HashWithoutSalt(): void
    {
        $password = 'testPassword123';

        $hashedPassword = $this->hash->encrypt($password);

        // Without a salt the result is a plain SHA-256 of the password.
        $this->assertSame(hash('sha256', $password), $hashedPassword);
    }

    public function testEncryptReturnsSixtyFourCharacterHexString(): void
    {
        $hashedPassword = $this->hash->encrypt('anyPassword');

        $this->assertSame(64, strlen($hashedPassword));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hashedPassword);
    }

    public function testEncryptIsDeterministicForSamePassword(): void
    {
        $password = 'repeatablePassword';

        $this->assertSame($this->hash->encrypt($password), $this->hash->encrypt($password));
    }

    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $this->assertNotSame($this->hash->encrypt('password123'), $this->hash->encrypt('password456'));
    }

    public function testEncryptUsesSalt(): void
    {
        $this->config->method('get')->willReturn('s3cr3tSalt');
        $login = 'admin';
        $password = 'myPassword';

        $this->hash->setSalt($login);

        // The salt is "security.salt" value concatenated with the login.
        $this->assertSame(hash('sha256', $password . 's3cr3tSalt' . $login), $this->hash->encrypt($password));
    }

    public function testSaltChangesTheResultingHash(): void
    {
        $this->config->method('get')->willReturn('s3cr3tSalt');
        $password = 'myPassword';

        $unsalted = $this->hash->encrypt($password);
        $this->hash->setSalt('admin');
        $salted = $this->hash->encrypt($password);

        $this->assertNotSame($unsalted, $salted);
    }

    public function testEncryptWithEmptyPassword(): void
    {
        $hashedPassword = $this->hash->encrypt('');

        $this->assertSame(hash('sha256', ''), $hashedPassword);
    }

    public function testEncryptWithSpecialCharacters(): void
    {
        $password = 'päßwörd!@#$%^&*()_+{}[]|\\:";\'<>?,./äöü';

        $hashedPassword = $this->hash->encrypt($password);

        $this->assertSame(hash('sha256', $password), $hashedPassword);
    }

    public function testEncryptWithUnicodeCharacters(): void
    {
        $password = '密码测试中文🔒🗝️🛡️';

        $hashedPassword = $this->hash->encrypt($password);

        $this->assertSame(hash('sha256', $password), $hashedPassword);
    }

    public function testEncryptWithLongPassword(): void
    {
        $password = str_repeat('a', 1000);

        $hashedPassword = $this->hash->encrypt($password);

        $this->assertSame(hash('sha256', $password), $hashedPassword);
    }
}
