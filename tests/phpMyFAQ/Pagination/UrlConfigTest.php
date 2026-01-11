<?php

namespace phpMyFAQ\Pagination;

use Error;
use PHPUnit\Framework\TestCase;

class UrlConfigTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $config = new UrlConfig();

        $this->assertEquals('page', $config->pageParamName);
        $this->assertEquals('', $config->seoName);
        $this->assertEquals('', $config->rewriteUrl);
    }

    public function testCustomPageParamName(): void
    {
        $config = new UrlConfig(pageParamName: 'seite');

        $this->assertEquals('seite', $config->pageParamName);
        $this->assertEquals('', $config->seoName);
        $this->assertEquals('', $config->rewriteUrl);
    }

    public function testCustomSeoName(): void
    {
        $config = new UrlConfig(seoName: 'faq');

        $this->assertEquals('page', $config->pageParamName);
        $this->assertEquals('faq', $config->seoName);
        $this->assertEquals('', $config->rewriteUrl);
    }

    public function testCustomRewriteUrl(): void
    {
        $config = new UrlConfig(rewriteUrl: '/page-%d.html');

        $this->assertEquals('page', $config->pageParamName);
        $this->assertEquals('', $config->seoName);
        $this->assertEquals('/page-%d.html', $config->rewriteUrl);
    }

    public function testAllCustomValues(): void
    {
        $config = new UrlConfig(pageParamName: 'p', seoName: 'articles', rewriteUrl: '/articles/page-%d');

        $this->assertEquals('p', $config->pageParamName);
        $this->assertEquals('articles', $config->seoName);
        $this->assertEquals('/articles/page-%d', $config->rewriteUrl);
    }

    public function testIsReadonly(): void
    {
        $config = new UrlConfig(pageParamName: 'test');

        $this->assertEquals('test', $config->pageParamName);

        // Readonly properties cannot be modified - this is enforced at compile time
        // We just verify the value is set correctly
        $this->expectException(Error::class);

        // @phpstan-ignore-next-line - This is intentionally testing readonly behavior
        $config->pageParamName = 'changed';
    }

    public function testImmutability(): void
    {
        $config1 = new UrlConfig(pageParamName: 'page1');
        $config2 = new UrlConfig(pageParamName: 'page2');

        $this->assertEquals('page1', $config1->pageParamName);
        $this->assertEquals('page2', $config2->pageParamName);

        // Each instance maintains its own values
        $this->assertNotEquals($config1->pageParamName, $config2->pageParamName);
    }

    public function testNamedParametersOrder(): void
    {
        // Test that parameters can be passed in any order
        $config = new UrlConfig(rewriteUrl: '/test-%d', pageParamName: 'p', seoName: 'test');

        $this->assertEquals('p', $config->pageParamName);
        $this->assertEquals('test', $config->seoName);
        $this->assertEquals('/test-%d', $config->rewriteUrl);
    }
}
