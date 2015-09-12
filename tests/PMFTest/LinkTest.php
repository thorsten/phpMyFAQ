<?php
/**
 * Test case for PMF_Link
 *
 * PHP Version 5.5
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
class PMFTest_LinkTest extends PHPUnit_Framework_TestCase
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
        $this->assertEquals(array('foo' => 'bar'), $method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar&amp;action=noaction', $this->config);
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar&action=noaction', $this->config);
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getQuery()
     */
    public function testgetQuery()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('getQuery');
        $method->setAccessible(true);

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar', $this->config);
        $this->assertEquals(array('main' => 'foo=bar'), $method->invokeArgs($this->link, array()));

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/?foo=bar#baz', $this->config);
        $this->assertEquals(array('main' => 'foo=bar', 'fragment' => 'baz'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getDefaultScheme()
     */
    public function testGetDefaultScheme()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('getDefaultScheme');
        $method->setAccessible(true);

        $this->config->config['security.useSslOnly'] = 'false';
        $this->link = new PMF_Link('http://faq.example.org/my-test-faq/', $this->config);
        $this->assertEquals('http://', $method->invokeArgs($this->link, array()));

        $this->config->config['security.useSslOnly'] = 'true';
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertEquals('https://', $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getSystemScheme()
     */
    public function testGetSystemScheme()
    {
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->assertEquals('https://', $this->link->getSystemScheme());
    }

    /**
     * Tests getSystemRelativeUri()
     */
    public function testGetSystemRelativeUri()
    {
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/inc/Link.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri());

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri('/index.php'));
    }

    /**
     * Tests getSystemUri()
     */
    public function testGetSystemUri()
    {
        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $_SERVER['HTTP_HOST']   = 'faq.example.org';
        $this->assertEquals('https://faq.example.org/my-test-faq/index.php', $this->link->getSystemUri());
        $this->assertEquals('https://faq.example.org/my-test-faq/', $this->link->getSystemUri('index.php'));
    }

    /**
     * Tests toHtmlAnchor()
     */
    public function testToHtmlAnchor()
    {
        $this->config->config['main.enableRewriteRules'] = true;

        $this->link = new PMF_Link('https://faq.example.org/my-test-faq/', $this->config);
        $this->link->class = 'pmf-foo';
        $this->assertEquals(
            '<a class="pmf-foo" href="https://faq.example.org/my-test-faq/">https://faq.example.org/my-test-faq/</a>',
            $this->link->toHtmlAnchor()
        );

        $this->link->id = 'pmf-id';
        $this->assertEquals(
            '<a class="pmf-foo" id="pmf-id" href="https://faq.example.org/my-test-faq/">https://faq.example.org/my-test-faq/</a>',
            $this->link->toHtmlAnchor()
        );

        $this->link->text = 'Foo FAQ';
        $this->assertEquals(
            '<a class="pmf-foo" id="pmf-id" href="https://faq.example.org/my-test-faq/">Foo FAQ</a>',
            $this->link->toHtmlAnchor()
        );
    }

    /**
     * Tests appendSids()
     */
    public function testAppendSids()
    {
        $class  = new ReflectionClass('PMF_Link');
        $method = $class->getMethod('appendSids');
        $method->setAccessible(true);

        $this->link = new PMF_Link('http://faq.example.org/my-test-faq/', $this->config);
        $actual     = $method->invokeArgs($this->link,array('http://faq.example.org/my-test-faq/', 'foo'));
        $expected   = 'http://faq.example.org/my-test-faq/?SIDS=foo';

        $this->assertEquals($expected,  $actual);
    }

    /**
     * Tests toString()
     */
    public function testToString()
    {
        $this->config->config['main.enableRewriteRules'] = true;

        $this->link = new PMF_Link('http://faq.example.org/my-test-faq/', $this->config);
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/',
            $this->link->toString()
        );

        $this->link = new PMF_Link('http://faq.example.org/my-test-faq/index.php?action=add', $this->config);
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/addcontent.html',
            $this->link->toString()
        );

        $this->link = new PMF_Link(
            'http://faq.example.org/my-test-faq/index.php?action=artikel&cat=1&id=36&artlang=de',
            $this->config
        );
        $this->link->itemTitle = 'HD Ready';
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/content/1/36/de/hd-ready.html',
            $this->link->toString()
        );
    }
}
