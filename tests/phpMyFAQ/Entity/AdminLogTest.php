<?php

namespace phpMyFAQ\Entity;

use PHPUnit\Framework\TestCase;

/**
 * Class AdminLogTest
 */
class AdminLogTest extends TestCase
{
    private AdminLog $adminLog;

    protected function setUp(): void
    {
        $this->adminLog = new AdminLog();
    }

    /**
     * Test AdminLog entity instantiation
     */
    public function testAdminLogInstantiation(): void
    {
        $this->assertInstanceOf(AdminLog::class, $this->adminLog);
    }

    /**
     * Test default values
     */
    public function testDefaultValues(): void
    {
        $this->assertEquals(0, $this->adminLog->getId());
        $this->assertEquals(0, $this->adminLog->getTime());
        $this->assertEquals(0, $this->adminLog->getUserId());
        $this->assertEquals('', $this->adminLog->getText());
        $this->assertEquals('', $this->adminLog->getIp());
        $this->assertNull($this->adminLog->getHash());
        $this->assertNull($this->adminLog->getPreviousHash());
    }

    /**
     * Test id getter and setter
     */
    public function testIdGetterAndSetter(): void
    {
        $id = 123;
        $result = $this->adminLog->setId($id);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($id, $this->adminLog->getId());
    }

    /**
     * Test time getter and setter
     */
    public function testTimeGetterAndSetter(): void
    {
        $time = 1717000000;
        $result = $this->adminLog->setTime($time);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($time, $this->adminLog->getTime());
    }

    /**
     * Test userId getter and setter
     */
    public function testUserIdGetterAndSetter(): void
    {
        $userId = 42;
        $result = $this->adminLog->setUserId($userId);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($userId, $this->adminLog->getUserId());
    }

    /**
     * Test text getter and setter
     */
    public function testTextGetterAndSetter(): void
    {
        $text = 'Admin performed an action';
        $result = $this->adminLog->setText($text);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($text, $this->adminLog->getText());
    }

    /**
     * Test ip getter and setter
     */
    public function testIpGetterAndSetter(): void
    {
        $ip = '192.168.1.1';
        $result = $this->adminLog->setIp($ip);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($ip, $this->adminLog->getIp());
    }

    /**
     * Test hash getter and setter
     */
    public function testHashGetterAndSetter(): void
    {
        $hash = str_repeat('a', 64);
        $result = $this->adminLog->setHash($hash);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($hash, $this->adminLog->getHash());
    }

    /**
     * Test hash setter accepts null
     */
    public function testHashCanBeSetToNull(): void
    {
        $this->adminLog->setHash('something');
        $this->assertEquals('something', $this->adminLog->getHash());

        $this->adminLog->setHash(null);
        $this->assertNull($this->adminLog->getHash());
    }

    /**
     * Test previousHash getter and setter
     */
    public function testPreviousHashGetterAndSetter(): void
    {
        $previousHash = str_repeat('b', 64);
        $result = $this->adminLog->setPreviousHash($previousHash);

        $this->assertInstanceOf(AdminLog::class, $result); // Test fluent interface
        $this->assertEquals($previousHash, $this->adminLog->getPreviousHash());
    }

    /**
     * Test previousHash setter accepts null
     */
    public function testPreviousHashCanBeSetToNull(): void
    {
        $this->adminLog->setPreviousHash('prev');
        $this->assertEquals('prev', $this->adminLog->getPreviousHash());

        $this->adminLog->setPreviousHash(null);
        $this->assertNull($this->adminLog->getPreviousHash());
    }

    /**
     * Test fluent interface with method chaining
     */
    public function testFluentInterface(): void
    {
        $result = $this->adminLog
            ->setId(1)
            ->setTime(1717000000)
            ->setUserId(7)
            ->setText('Logged in')
            ->setIp('10.0.0.1')
            ->setHash(str_repeat('c', 64))
            ->setPreviousHash(str_repeat('d', 64));

        $this->assertInstanceOf(AdminLog::class, $result);
        $this->assertEquals(1, $this->adminLog->getId());
        $this->assertEquals(1717000000, $this->adminLog->getTime());
        $this->assertEquals(7, $this->adminLog->getUserId());
        $this->assertEquals('Logged in', $this->adminLog->getText());
        $this->assertEquals('10.0.0.1', $this->adminLog->getIp());
        $this->assertEquals(str_repeat('c', 64), $this->adminLog->getHash());
        $this->assertEquals(str_repeat('d', 64), $this->adminLog->getPreviousHash());
    }

    /**
     * Test zero values
     */
    public function testZeroValues(): void
    {
        $this->adminLog->setId(0)->setTime(0)->setUserId(0);

        $this->assertEquals(0, $this->adminLog->getId());
        $this->assertEquals(0, $this->adminLog->getTime());
        $this->assertEquals(0, $this->adminLog->getUserId());
    }

    /**
     * Test negative values
     */
    public function testNegativeValues(): void
    {
        $this->adminLog->setId(-1)->setTime(-100)->setUserId(-5);

        $this->assertEquals(-1, $this->adminLog->getId());
        $this->assertEquals(-100, $this->adminLog->getTime());
        $this->assertEquals(-5, $this->adminLog->getUserId());
    }

    /**
     * Test integer boundaries
     */
    public function testIntegerBoundaries(): void
    {
        $this->adminLog->setTime(PHP_INT_MAX);
        $this->assertEquals(PHP_INT_MAX, $this->adminLog->getTime());

        $this->adminLog->setTime(PHP_INT_MIN);
        $this->assertEquals(PHP_INT_MIN, $this->adminLog->getTime());
    }

    /**
     * Test empty string values
     */
    public function testEmptyStringValues(): void
    {
        $this->adminLog->setText('')->setIp('');

        $this->assertEquals('', $this->adminLog->getText());
        $this->assertEquals('', $this->adminLog->getIp());
    }

    /**
     * Test text with multiline and special characters
     */
    public function testTextWithSpecialCharacters(): void
    {
        $text = "Line 1\nLine 2\tTabbed äöü <html> & \"quotes\"";
        $this->adminLog->setText($text);
        $this->assertEquals($text, $this->adminLog->getText());
    }

    /**
     * Test various IP address formats
     */
    public function testVariousIpFormats(): void
    {
        $ipAddresses = [
            '127.0.0.1',
            '192.168.1.1',
            '2001:db8::1',
            '::1',
            'unknown',
        ];

        foreach ($ipAddresses as $ip) {
            $this->adminLog->setIp($ip);
            $this->assertEquals($ip, $this->adminLog->getIp());
        }
    }

    /**
     * Test calculateHash returns a 64-character hex sha256 hash
     */
    public function testCalculateHashReturnsValidSha256(): void
    {
        $this->adminLog
            ->setTime(1717000000)
            ->setUserId(7)
            ->setIp('10.0.0.1')
            ->setText('Logged in')
            ->setPreviousHash(null);

        $hash = $this->adminLog->calculateHash();

        $this->assertIsString($hash);
        $this->assertEquals(64, strlen($hash));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $hash);
    }

    /**
     * Test calculateHash is deterministic for identical data
     */
    public function testCalculateHashIsDeterministic(): void
    {
        $this->adminLog->setTime(100)->setUserId(1)->setIp('1.1.1.1')->setText('x');

        $first = $this->adminLog->calculateHash();
        $second = $this->adminLog->calculateHash();

        $this->assertEquals($first, $second);
    }

    /**
     * Test calculateHash changes when data changes
     */
    public function testCalculateHashChangesWithData(): void
    {
        $this->adminLog->setTime(100)->setUserId(1)->setIp('1.1.1.1')->setText('x');
        $original = $this->adminLog->calculateHash();

        $this->adminLog->setText('y');
        $changed = $this->adminLog->calculateHash();

        $this->assertNotEquals($original, $changed);
    }

    /**
     * Test calculateHash matches an externally computed sha256
     */
    public function testCalculateHashMatchesExpectedValue(): void
    {
        $this->adminLog
            ->setTime(1717000000)
            ->setUserId(7)
            ->setIp('10.0.0.1')
            ->setText('Logged in')
            ->setPreviousHash('abc');

        $expected = hash('sha256', implode('|', ['1717000000', '7', '10.0.0.1', 'Logged in', 'abc']));

        $this->assertEquals($expected, $this->adminLog->calculateHash());
    }

    /**
     * Test verifyIntegrity returns false when hash is null
     */
    public function testVerifyIntegrityReturnsFalseWhenHashIsNull(): void
    {
        $this->adminLog->setTime(100)->setUserId(1)->setIp('1.1.1.1')->setText('x');

        $this->assertFalse($this->adminLog->verifyIntegrity());
    }

    /**
     * Test verifyIntegrity returns true for a matching stored hash
     */
    public function testVerifyIntegrityReturnsTrueForValidHash(): void
    {
        $this->adminLog->setTime(100)->setUserId(1)->setIp('1.1.1.1')->setText('x');
        $this->adminLog->setHash($this->adminLog->calculateHash());

        $this->assertTrue($this->adminLog->verifyIntegrity());
    }

    /**
     * Test verifyIntegrity returns false for a tampered record
     */
    public function testVerifyIntegrityReturnsFalseWhenTampered(): void
    {
        $this->adminLog->setTime(100)->setUserId(1)->setIp('1.1.1.1')->setText('x');
        $this->adminLog->setHash($this->adminLog->calculateHash());

        // Tamper with the data after the hash was stored.
        $this->adminLog->setText('tampered');

        $this->assertFalse($this->adminLog->verifyIntegrity());
    }
}
