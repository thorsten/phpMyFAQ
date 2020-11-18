<?php

/**
 * Test case for Link
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012 phpMyFAQ Team
 * @license http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2012-03-29
 */

use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;
use PHPUnit\Framework\TestCase;

/**
 * Class LinkTest
 */
class LinkTest extends TestCase
{
    /** @var Link */
    private $link;

    /** @var Configuration */
    private $Configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init('en');

        $_SERVER['HTTP_HOST'] = 'faq.example.org';

        $dbHandle = new Sqlite3();
        $this->Configuration = new Configuration($dbHandle);
        $this->Configuration->config['security.useSslOnly'] = 'true';
    }

    protected function tearDown(): void
    {
        $this->link = null;
        parent::tearDown();
    }

    public function testisHomeIndex()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isHomeIndex');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('https://faq.example.org/my-test-faq/index.php', $this->Configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    public function testisInternalReference()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isInternalReference');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('https://faq.example.org/my-test-faq/index.php#foobar', $this->Configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('#foobar', $this->Configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests isSystemLink()
     */
    public function testIsSystemLink()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isSystemLink');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * tests hasScheme()
     */
    public function testHasScheme()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('hasScheme');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));

        $this->link = new Link('faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getSEOItemTitle()
     */
    public function testGetSEOItemTitle()
    {
        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);

        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOItemTitle('HD Ready')
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOItemTitle('HD Ready                     ')
        );
        $this->assertEquals(
            'hd_ready',
            $this->link->getSEOItemTitle('HD-Ready')
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOItemTitle("HD\r\nReady")
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOItemTitle('{HD + Ready}')
        );
        $this->assertEquals(
            'hd-raedy',
            $this->link->getSEOItemTitle('HD Rädy')
        );
    }

    /**
     * Tests getHttpGetParameters()
     */
    public function testGetHttpGetParameters()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getHttpGetParameters');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/?foo=bar', $this->Configuration);
        $this->assertEquals(array('foo' => 'bar'), $method->invokeArgs($this->link, array()));

        $this->link = new Link(
            'https://faq.example.org/my-test-faq/?foo=bar&amp;action=noaction',
            $this->Configuration
        );
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));

        $this->link = new Link('https://faq.example.org/my-test-faq/?foo=bar&action=noaction', $this->Configuration);
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getQuery()
     */
    public function testgetQuery()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getQuery');
        $method->setAccessible(true);

        $this->link = new Link('https://faq.example.org/my-test-faq/?foo=bar', $this->Configuration);
        $this->assertEquals(array('main' => 'foo=bar'), $method->invokeArgs($this->link, array()));

        $this->link = new Link('https://faq.example.org/my-test-faq/?foo=bar#baz', $this->Configuration);
        $this->assertEquals(array('main' => 'foo=bar', 'fragment' => 'baz'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getDefaultScheme()
     */
    public function testGetDefaultScheme()
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getDefaultScheme');
        $method->setAccessible(true);

        $this->Configuration->config['security.useSslOnly'] = 'false';
        $this->link = new Link('http://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertEquals('http://', $method->invokeArgs($this->link, array()));

        $this->Configuration->config['security.useSslOnly'] = 'true';
        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertEquals('https://', $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getSystemScheme()
     */
    public function testGetSystemScheme()
    {
        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertEquals('https://', $this->link->getSystemScheme());
    }

    /**
     * Tests getSystemRelativeUri()
     */
    public function testGetSystemRelativeUri()
    {
        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/src/Link.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri());

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri('/index.php'));
    }

    /**
     * Tests getSystemUri()
     */
    public function testGetSystemUri()
    {
        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $_SERVER['HTTP_HOST'] = 'faq.example.org';
        $this->assertEquals('https://faq.example.org/my-test-faq/index.php', $this->link->getSystemUri());
        $this->assertEquals('https://faq.example.org/my-test-faq/', $this->link->getSystemUri('index.php'));
    }

    /**
     * Tests toHtmlAnchor()
     */
    public function testToHtmlAnchor()
    {
        $this->Configuration->config['main.enableRewriteRules'] = true;

        $this->link = new Link('https://faq.example.org/my-test-faq/', $this->Configuration);
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
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('appendSids');
        $method->setAccessible(true);

        $this->link = new Link('http://faq.example.org/my-test-faq/', $this->Configuration);
        $actual = $method->invokeArgs($this->link, array('http://faq.example.org/my-test-faq/', 4711));
        $expected = 'http://faq.example.org/my-test-faq/?sid=4711';

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests toString()
     * Rewrite rules enabled.
     */
    public function testToStringWithEnabledRewriteRules()
    {
        $this->Configuration->config['main.enableRewriteRules'] = true;

        $this->link = new Link('http://faq.example.org/my-test-faq/', $this->Configuration);
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/',
            $this->link->toString()
        );

        $this->link = new Link('http://faq.example.org/my-test-faq/index.php?action=add', $this->Configuration);
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/addcontent.html',
            $this->link->toString()
        );

        $this->link = new Link(
            'http://faq.example.org/my-test-faq/index.php?action=faq&cat=1&id=36&artlang=de',
            $this->Configuration
        );
        $this->link->itemTitle = 'HD Ready';
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/content/1/36/de/hd-ready.html',
            $this->link->toString()
        );
    }

    /**
     * Tests toString()
     * Rewrite rules disabled.
     */
    public function testToStringWithDisabledRewriteRules()
    {
        $this->Configuration->config['main.enableRewriteRules'] = false;

        $url = 'http://faq.example.org/my-test-faq/';
        $this->link = new Link($url, $this->Configuration);
        $this->assertEquals($url, $this->link->toString(true));

        $url = 'http://faq.example.org/my-test-faq/index.php?action=add';
        $this->link = new Link($url, $this->Configuration);
        $this->assertEquals($url, $this->link->toString(true));

        $url = 'http://faq.example.org/my-test-faq/index.php?sid=4711&action=faq&cat=1&id=36&artlang=de';
        $this->link = new Link($url, $this->Configuration);
        $this->assertEquals(
            'http://faq.example.org/my-test-faq/index.php?action=faq&cat=1&id=36&artlang=de',
            $this->link->toString(true)
        );
    }
}
