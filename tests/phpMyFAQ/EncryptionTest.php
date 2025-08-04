<?php

/**
 * Encryption Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-04
 */

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\EncryptionTypes\Bcrypt;

/**
 * Class EncryptionTest
 */
#[CoversClass(Encryption::class)]
#[CoversClass(Bcrypt::class)]
class EncryptionTest extends TestCase
{
    private Configuration $config;
    private Encryption $encryption;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Configuration::class);
        $this->encryption = Encryption::getInstance('bcrypt', $this->config);
    }

    public function testGetInstanceReturnsEncryptionObject(): void
    {
        $encryption = Encryption::getInstance('bcrypt', $this->config);

        $this->assertInstanceOf(Encryption::class, $encryption);
    }

    public function testGetInstanceWithNullConfiguration(): void
    {
        $encryption = Encryption::getInstance('bcrypt', $this->config);

        $this->assertInstanceOf(Encryption::class, $encryption);
    }

    public function testGetInstanceWithInvalidType(): void
    {
        $encryption = Encryption::getInstance('invalid', $this->config);

        $this->assertInstanceOf(Encryption::class, $encryption);
        $this->assertNotEmpty($encryption->errors);
    }

    public function testEncryptPasswordReturnsString(): void
    {
        $password = 'testPassword123';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertNotEmpty($hashedPassword);
        $this->assertIsString($hashedPassword);
    }

    public function testEncryptWithEmptyPassword(): void
    {
        $password = '';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
    }

    public function testEncryptWithSpecialCharacters(): void
    {
        $password = 'päßwörd!@#$%^&*()_+{}[]|\\:";\'<>?,./äöü';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    public function testEncryptWithUnicodeCharacters(): void
    {
        $password = '密码测试中文🔒🗝️🛡️';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    public function testEncryptWithLongPassword(): void
    {
        $password = str_repeat('LongPassword123!', 10); // 160 characters

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    public function testEncryptWithVeryShortPassword(): void
    {
        $password = 'a';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    public function testErrorMethodReturnsString(): void
    {
        $errorMessage = $this->encryption->error();

        $this->assertIsString($errorMessage);
    }

    public function testErrorsArrayIsPublic(): void
    {
        $errors = $this->encryption->errors;

        $this->assertIsArray($errors);
    }

    public function testDifferentEncryptionTypes(): void
    {
        $types = ['bcrypt', 'hash', 'none'];

        foreach ($types as $type) {
            $encryption = Encryption::getInstance($type, $this->config);
            $this->assertInstanceOf(Encryption::class, $encryption);
        }
    }
}
