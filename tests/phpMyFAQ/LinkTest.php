<?php

namespace phpMyFAQ;

use phpMyFAQ\Database\Sqlite3;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class LinkTest
 */
class LinkTest extends TestCase
{
    private Link $link;

    private Configuration $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        $_SERVER['HTTP_HOST'] = 'example.com';

        $dbHandle = new Sqlite3();
        $dbHandle->connect(PMF_TEST_DIR . '/test.db', '', '');

        $this->configuration = new Configuration($dbHandle);
        $this->configuration->set('security.useSslOnly', 'true');
    }

    public function testisHomeIndex(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isHomeIndex');

        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('https://example.com/my-test-faq/index.php', $this->configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    public function testisInternalReference(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isInternalReference');

        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('https://example.com/my-test-faq/index.php#foobar', $this->configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));

        $this->link = new Link('#foobar', $this->configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * Tests isSystemLink()
     */
    public function testIsSystemLink(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('isSystemLink');

        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));
    }

    /**
     * tests hasScheme()
     */
    public function testHasScheme(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('hasScheme');

        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertTrue($method->invokeArgs($this->link, array()));

        $this->link = new Link('example.com/my-test-faq/', $this->configuration);
        $this->assertFalse($method->invokeArgs($this->link, array()));
    }

    public function testGetSEOTitle(): void
    {
        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);

        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOTitle('HD Ready')
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOTitle('HD Ready                     ')
        );
        $this->assertEquals(
            'hd_ready',
            $this->link->getSEOTitle('HD-Ready')
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOTitle("HD\r\nReady")
        );
        $this->assertEquals(
            'hd-ready',
            $this->link->getSEOTitle('{HD + Ready}')
        );
        $this->assertEquals(
            'hd-raedy',
            $this->link->getSEOTitle('HD Rädy')
        );
    }

    /**
     * Tests getHttpGetParameters()
     */
    public function testGetHttpGetParameters(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getHttpGetParameters');

        $this->link = new Link('https://example.com/my-test-faq/?foo=bar', $this->configuration);
        $this->assertEquals(array('foo' => 'bar'), $method->invokeArgs($this->link, array()));

        $this->link = new Link(
            'https://example.com/my-test-faq/?foo=bar&amp;action=noaction',
            $this->configuration
        );
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));

        $this->link = new Link('https://example.com/my-test-faq/?foo=bar&action=noaction', $this->configuration);
        $this->assertEquals(array('foo' => 'bar', 'action' => 'noaction'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getQuery()
     */
    public function testgetQuery(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getQuery');

        $this->link = new Link('https://example.com/my-test-faq/?foo=bar', $this->configuration);
        $this->assertEquals(array('main' => 'foo=bar'), $method->invokeArgs($this->link, array()));

        $this->link = new Link('https://example.com/my-test-faq/?foo=bar#baz', $this->configuration);
        $this->assertEquals(array('main' => 'foo=bar', 'fragment' => 'baz'), $method->invokeArgs($this->link, array()));
    }

    /**
     * Tests getDefaultScheme()
     */
    public function testGetDefaultScheme(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('getDefaultScheme');

        $this->configuration->set('security.useSslOnly', 'true');
        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertEquals('https://', $method->invokeArgs($this->link, []));
    }

    /**
     * Tests getSystemScheme()
     */
    public function testGetSystemScheme(): void
    {
        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);
        $this->assertEquals('https://', $this->link->getSystemScheme());
    }

    /**
     * Tests getSystemRelativeUri()
     */
    public function testGetSystemRelativeUri(): void
    {
        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/src/Link.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri());

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $this->assertEquals('/my-test-faq', $this->link->getSystemRelativeUri('/index.php'));
    }

    /**
     * Tests getSystemUri()
     */
    public function testGetSystemUri(): void
    {
        $this->link = new Link('https://example.com/my-test-faq/', $this->configuration);

        $_SERVER['SCRIPT_NAME'] = '/my-test-faq/index.php';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertEquals('https://example.com/my-test-faq/index.php', $this->link->getSystemUri());
        $this->assertEquals('https://example.com/my-test-faq/', $this->link->getSystemUri('index.php'));
    }

    /**
     * Tests toHtmlAnchor()
     */
    public function testToHtmlAnchor(): void
    {
        $url = 'https://example.com/my-test-faq/';

        $this->link = new Link($url, $this->configuration);
        $this->link->class = 'pmf-foo';
        $this->assertEquals(
            sprintf(
                '<a class="pmf-foo" href="%s">%s</a>',
                $url,
                $url
            ),
            $this->link->toHtmlAnchor()
        );

        $this->link->id = 'pmf-id';
        $this->assertEquals(
            sprintf(
                '<a class="pmf-foo" id="pmf-id" href="%s">%s</a>',
                $url,
                $url
            ),
            $this->link->toHtmlAnchor()
        );

        $this->link->text = 'Foo FAQ';
        $this->assertEquals(
            sprintf('<a class="pmf-foo" id="pmf-id" href="%s">Foo FAQ</a>', $url),
            $this->link->toHtmlAnchor()
        );
    }

    /**
     * Tests appendSids()
     */
    public function testAppendSids(): void
    {
        $class = new ReflectionClass('phpMyFAQ\Link');
        $method = $class->getMethod('appendSessionId');

        $this->link = new Link('http://example.com/my-test-faq/', $this->configuration);
        $actual = $method->invokeArgs($this->link, array('http://example.com/my-test-faq/', 4711));
        $expected = 'http://example.com/my-test-faq/?sid=4711';

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests toString()
     * Rewrite rules enabled.
     */
    public function testToStringWithEnabledRewriteRules(): void
    {
        $this->link = new Link('http://example.com/my-test-faq/', $this->configuration);
        $this->assertEquals(
            'http://example.com/my-test-faq/',
            $this->link->toString()
        );

        $this->link = new Link('http://example.com/my-test-faq/index.php?action=add', $this->configuration);
        $this->assertEquals(
            'http://example.com/my-test-faq/add-faq.html',
            $this->link->toString()
        );

        $this->link = new Link('http://example.com/my-test-faq/index.php?action=bookmarks', $this->configuration);
        $this->assertEquals(
            'http://example.com/my-test-faq/user/bookmarks',
            $this->link->toString()
        );

        $this->link = new Link(
            'http://example.com/my-test-faq/index.php?action=faq&cat=1&id=36&artlang=de',
            $this->configuration
        );
        $this->link->setTitle('HD Ready');
        $this->assertEquals(
            'http://example.com/my-test-faq/content/1/36/de/hd-ready.html',
            $this->link->toString()
        );
    }

    /**
     * Tests toString()
     * Rewrite rules disabled.
     */
    public function testToStringWithDisabledRewriteRules(): void
    {
        $url = 'https://example.com/my-test-faq/';
        $this->link = new Link($url, $this->configuration);
        $this->assertEquals($url, $this->link->toStringWithoutSession());

        $url = 'https://example.com/my-test-faq/add-faq.html';
        $this->link = new Link($url, $this->configuration);
        $this->assertEquals($url, $this->link->toStringWithoutSession());

        $url = 'https://example.com/my-test-faq/index.php?sid=4711&action=faq&cat=1&id=36&artlang=de';
        $this->link = new Link($url, $this->configuration);
        $this->link->setTitle('Foobar');
        $this->assertEquals(
            'https://example.com/my-test-faq/content/1/36/de/foobar.html',
            $this->link->toStringWithoutSession()
        );
    }
}
