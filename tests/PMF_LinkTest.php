<?php
/**
 * Test case for PMF_Link
 *
 * PHP Version 5.3
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */


require_once dirname(__DIR__) . '/phpmyfaq/inc/PMF/DB/Driver.php';
require_once dirname(__DIR__) . '/phpmyfaq/inc/PMF/DB/Sqlite3.php';
require_once dirname(__DIR__) . '/phpmyfaq/inc/PMF/Exception.php';
require_once dirname(__DIR__) . '/phpmyfaq/inc/PMF/Configuration.php';
require_once dirname(__DIR__) . '/phpmyfaq/inc/PMF/Link.php';

/**
 * PMF_LinkTest
 *
 * @category  phpMyFAQ
 * @package   PMF_Tests
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      http://www.phpmyfaq.de
 * @since     2012-03-29
 */
class PMF_LinkTest extends PHPUnit_Framework_TestCase
{
    /** @var PMF_DB_Sqlite3 */
    private $dbHandle;

    /** @var PMF_Link */
    private $link;

    /** @var PMF_Configuration */
    private $config;

    /**
     * Prepares the environment before running a test.
     */
    protected function setUp ()
    {
        parent::setUp();

        PMF_String::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $this->dbHandle = new PMF_DB_Sqlite3();
        $this->config   = new PMF_Configuration($this->dbHandle);
        $this->config->config['security.useSslOnly'] = 'true';
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected function tearDown ()
    {
        $this->link = null;
        parent::tearDown();
    }

    /**
     * Tests isHomeIndex()
     */
    public function testisHomeIndex()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('isHomeIndex');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/index.php', $this->config);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests isInternalReference()
     */
    public function testisInternalReference()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('isInternalReference');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/index.php#foobar', $this->config);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('#foobar', $this->config);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests isSystemLink()
     */
    public function testIsSystemLink()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('isSystemLink');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * tests hasScheme()
     */
    public function testHasScheme()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('hasScheme');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertTrue($method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('faq.example.org/my-test-faq/', $this->config);
        $this->assertFalse($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getSEOItemTitle()
     */
    public function testGetSEOItemTitle()
    {
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);

        $this->assertEquals(
            'hd-ready', $this->link->getSEOItemTitle('HD Ready')
        );
        $this->assertEquals(
            'hd-ready', $this->link->getSEOItemTitle('HD Ready                     ')
        );
        $this->assertEquals(
            'hd_ready', $this->link->getSEOItemTitle('HD-Ready')
        );
        $this->assertEquals(
            'hd-ready', $this->link->getSEOItemTitle("HD\r\nReady")
        );
        $this->assertEquals(
            'hd-ready', $this->link->getSEOItemTitle('{HD + Ready}')
        );
        $this->assertEquals(
            'hd-raedy', $this->link->getSEOItemTitle('HD Rädy')
        );
    }

    /**
     * Tests getHttpGetParameters()
     */
    public function testGetHttpGetParameters()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('getHttpGetParameters');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar', $this->config);
        $this->assertEquals(['foo' => 'bar'], $method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar&amp;action=noaction', $this->config);
        $this->assertEquals(['foo' => 'bar', 'action' => 'noaction'], $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getSystemScheme()
     */
    public function testGetSystemScheme()
    {
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertEquals('https://', $this->link->getSystemScheme());
    }
}
