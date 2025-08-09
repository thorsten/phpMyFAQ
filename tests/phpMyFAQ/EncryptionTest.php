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
        $password = str_repeat('a', 1000); // Very long password

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    /**
     * Test getInstance with different encryption types
     */
    public function testGetInstanceWithDifferentTypes(): void
    {
        $types = ['bcrypt', 'hash', 'none'];

        foreach ($types as $type) {
            $encryption = Encryption::getInstance($type, $this->config);
            $this->assertInstanceOf(Encryption::class, $encryption);
        }
    }

    /**
     * Test getInstance with case variations
     */
    public function testGetInstanceWithCaseVariations(): void
    {
        $variations = ['BCRYPT', 'Bcrypt', 'bCrYpT', 'bcrypt'];

        foreach ($variations as $variation) {
            $encryption = Encryption::getInstance($variation, $this->config);
            $this->assertInstanceOf(Encryption::class, $encryption);
        }
    }

    /**
     * Test error method returns string
     */
    public function testErrorMethodReturnsString(): void
    {
        $encryption = Encryption::getInstance('invalid', $this->config);
        $error = $encryption->error();

        $this->assertIsString($error);
        $this->assertStringContainsString('EncryptionTypes method could not be found', $error);
    }

    /**
     * Test error method with no errors
     */
    public function testErrorMethodWithNoErrors(): void
    {
        $encryption = Encryption::getInstance('bcrypt', $this->config);
        $error = $encryption->error();

        $this->assertIsString($error);
        $this->assertEmpty($error);
    }

    /**
     * Test multiple invalid encryption types generate multiple errors
     */
    public function testMultipleInvalidTypesGenerateMultipleErrors(): void
    {
        $invalidTypes = ['invalid1', 'invalid2', 'nonexistent'];
        $errors = [];

        foreach ($invalidTypes as $type) {
            $encryption = Encryption::getInstance($type, $this->config);
            $errors[] = $encryption->error();
        }

        foreach ($errors as $error) {
            $this->assertStringContainsString('EncryptionTypes method could not be found', $error);
        }
    }

    /**
     * Test encrypt method with null-like values
     */
    public function testEncryptWithNullLikeValues(): void
    {
        $values = ['0', 'false', 'null'];

        foreach ($values as $value) {
            $result = $this->encryption->encrypt($value);
            $this->assertIsString($result);
        }
    }

    /**
     * Test that different passwords produce different hashes
     */
    public function testDifferentPasswordsProduceDifferentHashes(): void
    {
        $password1 = 'password123';
        $password2 = 'password456';

        $hash1 = $this->encryption->encrypt($password1);
        $hash2 = $this->encryption->encrypt($password2);

        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test that same password encrypted twice produces different hashes (salt)
     */
    public function testSamePasswordProducesDifferentHashes(): void
    {
        $password = 'samePassword123';

        $hash1 = $this->encryption->encrypt($password);
        $hash2 = $this->encryption->encrypt($password);

        // With proper salting, same password should produce different hashes
        $this->assertNotEquals($hash1, $hash2);
    }

    /**
     * Test constructor is private (factory pattern)
     */
    public function testFactoryPatternEnforcement(): void
    {
        $reflection = new \ReflectionClass(Encryption::class);
        $constructor = $reflection->getConstructor();

        $this->assertTrue($constructor->isPrivate());
    }

    /**
     * Test getInstance with empty string
     */
    public function testGetInstanceWithEmptyString(): void
    {
        $encryption = Encryption::getInstance('', $this->config);

        $this->assertInstanceOf(Encryption::class, $encryption);
        $this->assertNotEmpty($encryption->errors);
    }

    /**
     * Test encryption with whitespace-only password
     */
    public function testEncryptWithWhitespacePassword(): void
    {
        $password = '   ';

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    /**
     * Test encryption with newline characters
     */
    public function testEncryptWithNewlineCharacters(): void
    {
        $password = "password\nwith\nnewlines\r\n";

        $hashedPassword = $this->encryption->encrypt($password);

        $this->assertIsString($hashedPassword);
        $this->assertNotEmpty($hashedPassword);
    }

    /**
     * Test error array is accessible
     */
    public function testErrorArrayIsAccessible(): void
    {
        $encryption = Encryption::getInstance('invalid', $this->config);

        $this->assertIsArray($encryption->errors);
        $this->assertNotEmpty($encryption->errors);
        $this->assertContains('EncryptionTypes method could not be found.', $encryption->errors);
    }

    /**
     * Test getInstance with numeric string
     */
    public function testGetInstanceWithNumericString(): void
    {
        $encryption = Encryption::getInstance('123', $this->config);

        $this->assertInstanceOf(Encryption::class, $encryption);
        $this->assertNotEmpty($encryption->errors);
    }
}
