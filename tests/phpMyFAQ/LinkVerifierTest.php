<?php
/**
 * Test case for Linkverifier
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-03-29
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\LinkVerifier;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class LinkVerifierTest
 */
class LinkVerifierTest extends TestCase
{
    /** @var  LinkVerifier */
    private $linkVerifier;

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
        $this->linkVerifier = null;
        parent::tearDown();
    }

    /**
     * Tests isReady()
     */
    public function testIsReady()
    {
        $this->assertFalse($this->linkVerifier->isReady());
    }

    /**
     * Tests addIgnoreProtocol()
     */
    public function testAddIgnoreProtocol()
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
