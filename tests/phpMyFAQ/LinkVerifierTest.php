<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

/**
 * Class LinkVerifierTest
 */
class LinkVerifierTest extends TestCase
{
    /** @var  LinkVerifier */
    private LinkVerifier $linkVerifier;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $config = new stdClass();
        $config->config_name = 'main.referenceURL';
        $config->config_value = 'http://localhost';

        $dbHandle = $this->getMockBuilder('phpMyFAQ\Database\Sqlite3')->disableOriginalConstructor()->getMock();
        $dbHandle->method('fetchAll')->willReturn([$config]);

        $config1 = new Configuration($dbHandle);
        $this->linkVerifier = new LinkVerifier($config1);
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Tests isReady()
     */
    public function testIsReady(): void
    {
        $this->assertFalse($this->linkVerifier->isReady());
    }

    /**
     * Tests addIgnoreProtocol()
     */
    public function testAddIgnoreProtocol(): void
    {
        $class  = new ReflectionClass('phpMyFAQ\LinkVerifier');
        $method = $class->getMethod('addIgnoreProtocol');
        $method->setAccessible(true);

        $this->assertFalse($method->invokeArgs($this->linkVerifier, ['']));
        $this->assertTrue($method->invokeArgs($this->linkVerifier, ['ftp:']));
    }

    /**
     * Tests parseString()
     */
    public function testParseString()
    {
        $html = '<a href="http://www.example.org">Test</a>';
        $this->assertEquals(1, $this->linkVerifier->parseString($html));

        $html .= '<img src="http://www.example.com/foo.png">';
        $this->assertEquals(2, $this->linkVerifier->parseString($html));
    }
}
