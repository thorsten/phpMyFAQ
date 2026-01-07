<?php

namespace phpMyFAQ;

use phpMyFAQ\Pagination\PaginationTemplates;
use phpMyFAQ\Pagination\UrlConfig;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class PaginationTest extends TestCase
{
    public function testRender(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo',
            total: 30,
            perPage: 10,
        );

        $expectedOutput =
            '<ul class="pagination justify-content-center">'
            . '<li class="page-item active">'
            . '<a class="page-link" href="http://example.com/foo?page=1">1</a>'
            . '</li>&nbsp;&nbsp;<li class="page-item">'
            . '<a class="page-link" href="http://example.com/foo?page=2">2</a>'
            . '</li>&nbsp;&nbsp;<li class="page-item">'
            . '<a class="page-link" href="http://example.com/foo?page=3">3</a>'
            . '</li>&nbsp;&nbsp;<li class="page-item">'
            . '<a class="page-link" href="http://example.com/foo?page=2">&rarr;</a>'
            . '</li>&nbsp;&nbsp;<li class="page-item">'
            . '<a class="page-link" href="http://example.com/foo?page=3">&#8677;</a>'
            . '</li></ul>';

        $this->assertEquals($expectedOutput, $pagination->render());
    }

    public function testRenderWithCustomUrlConfig(): void
    {
        $urlConfig = new UrlConfig(pageParamName: 'seite');
        $pagination = new Pagination(
            baseUrl: 'http://example.com/faq',
            total: 20,
            perPage: 10,
            urlConfig: $urlConfig,
        );

        $output = $pagination->render();

        $this->assertStringContainsString('seite=1', $output);
        $this->assertStringContainsString('seite=2', $output);
    }

    public function testRenderWithCustomTemplates(): void
    {
        $templates = new PaginationTemplates(
            link: '<span class="custom"><a href="{LINK_URL}">{LINK_TEXT}</a></span>',
            currentPage: '<span class="active"><a href="{LINK_URL}">{LINK_TEXT}</a></span>',
        );

        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo',
            total: 20,
            perPage: 10,
            templates: $templates,
        );

        $output = $pagination->render();

        $this->assertStringContainsString('<span class="custom">', $output);
        $this->assertStringContainsString('<span class="active">', $output);
    }

    public function testRenderWithRewriteUrl(): void
    {
        $urlConfig = new UrlConfig(rewriteUrl: '/page-%d.html');
        $pagination = new Pagination(
            baseUrl: '',
            total: 30,
            perPage: 10,
            urlConfig: $urlConfig,
        );

        $output = $pagination->render();

        $this->assertStringContainsString('/page-1.html', $output);
        $this->assertStringContainsString('/page-2.html', $output);
        $this->assertStringContainsString('/page-3.html', $output);
    }

    public function testPropertyHookValidation(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo',
            total: -10,  // Should be converted to 0
            perPage: 0,  // Should be converted to 1
            adjacent: 0, // Should be converted to 1
        );

        // The render should not throw an error due to invalid values
        $output = $pagination->render();
        $this->assertIsString($output);
    }

    public function testCurrentPageExtraction(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo?page=2',
            total: 30,
            perPage: 10,
        );

        $output = $pagination->render();

        // Page 2 should be the active page
        $this->assertStringContainsString(
            '<li class="page-item active">',
            $output
        );
        $this->assertStringContainsString('>2</a>', $output);
    }

    public function testNavigationButtons(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo?page=2',
            total: 50,
            perPage: 10,
        );

        $output = $pagination->render();

        // Should have first, prev, next, and last buttons
        $this->assertStringContainsString('&#8676;', $output); // First
        $this->assertStringContainsString('&larr;', $output);  // Prev
        $this->assertStringContainsString('&rarr;', $output);  // Next
        $this->assertStringContainsString('&#8677;', $output); // Last
    }

    public function testNoNavigationOnFirstPage(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo',
            total: 30,
            perPage: 10,
        );

        $output = $pagination->render();

        // Should not have first and prev buttons on page 1
        $this->assertStringNotContainsString('&#8676;', $output); // First
        $this->assertStringNotContainsString('&larr;', $output);  // Prev
    }

    public function testNoNavigationOnLastPage(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo?page=3',
            total: 30,
            perPage: 10,
        );

        $output = $pagination->render();

        // Should not have next and last buttons on last page
        $this->assertStringNotContainsString('&rarr;', $output); // Next
        $this->assertStringNotContainsString('&#8677;', $output); // Last
    }

    public function testSinglePage(): void
    {
        $pagination = new Pagination(
            baseUrl: 'http://example.com/foo',
            total: 5,
            perPage: 10,
        );

        $output = $pagination->render();

        // Should only show page 1
        $this->assertStringContainsString(
            '<li class="page-item active"><a class="page-link" href="http://example.com/foo?page=1">1</a>',
            $output
        );
        $this->assertStringNotContainsString('page=2', $output);
    }
}
