<?php

namespace phpMyFAQ\Pagination;

use PHPUnit\Framework\TestCase;

class PaginationTemplatesTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $templates = new PaginationTemplates();

        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
            $templates->link
        );
        $this->assertEquals(
            '<li class="page-item active"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
            $templates->currentPage
        );
        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">&rarr;</a></li>',
            $templates->nextPage
        );
        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">&larr;</a></li>',
            $templates->prevPage
        );
        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8676;</a></li>',
            $templates->firstPage
        );
        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">&#8677;</a></li>',
            $templates->lastPage
        );
        $this->assertEquals(
            '<ul class="pagination justify-content-center">{LAYOUT_CONTENT}</ul>',
            $templates->layout
        );
    }

    public function testDefaultFactoryMethod(): void
    {
        $templates = PaginationTemplates::default();

        $this->assertInstanceOf(PaginationTemplates::class, $templates);
        $this->assertEquals(
            '<li class="page-item"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
            $templates->link
        );
    }

    public function testCustomLink(): void
    {
        $templates = new PaginationTemplates(
            link: '<span><a href="{LINK_URL}">{LINK_TEXT}</a></span>'
        );

        $this->assertEquals(
            '<span><a href="{LINK_URL}">{LINK_TEXT}</a></span>',
            $templates->link
        );
        // Other templates should keep their defaults
        $this->assertEquals(
            '<li class="page-item active"><a class="page-link" href="{LINK_URL}">{LINK_TEXT}</a></li>',
            $templates->currentPage
        );
    }

    public function testCustomCurrentPage(): void
    {
        $templates = new PaginationTemplates(
            currentPage: '<strong><a href="{LINK_URL}">{LINK_TEXT}</a></strong>'
        );

        $this->assertEquals(
            '<strong><a href="{LINK_URL}">{LINK_TEXT}</a></strong>',
            $templates->currentPage
        );
    }

    public function testCustomNavigationTemplates(): void
    {
        $templates = new PaginationTemplates(
            nextPage: '<button class="next" data-url="{LINK_URL}">Next</button>',
            prevPage: '<button class="prev" data-url="{LINK_URL}">Prev</button>',
            firstPage: '<button class="first" data-url="{LINK_URL}">First</button>',
            lastPage: '<button class="last" data-url="{LINK_URL}">Last</button>',
        );

        $this->assertEquals('<button class="next" data-url="{LINK_URL}">Next</button>', $templates->nextPage);
        $this->assertEquals('<button class="prev" data-url="{LINK_URL}">Prev</button>', $templates->prevPage);
        $this->assertEquals('<button class="first" data-url="{LINK_URL}">First</button>', $templates->firstPage);
        $this->assertEquals('<button class="last" data-url="{LINK_URL}">Last</button>', $templates->lastPage);
    }

    public function testCustomLayout(): void
    {
        $templates = new PaginationTemplates(
            layout: '<nav class="my-pagination">{LAYOUT_CONTENT}</nav>'
        );

        $this->assertEquals(
            '<nav class="my-pagination">{LAYOUT_CONTENT}</nav>',
            $templates->layout
        );
    }

    public function testAllCustomTemplates(): void
    {
        $templates = new PaginationTemplates(
            link: '<a href="{LINK_URL}">{LINK_TEXT}</a>',
            currentPage: '<span class="current">{LINK_TEXT}</span>',
            nextPage: '<a href="{LINK_URL}">Next</a>',
            prevPage: '<a href="{LINK_URL}">Prev</a>',
            firstPage: '<a href="{LINK_URL}">First</a>',
            lastPage: '<a href="{LINK_URL}">Last</a>',
            layout: '<div class="pagination">{LAYOUT_CONTENT}</div>',
        );

        $this->assertEquals('<a href="{LINK_URL}">{LINK_TEXT}</a>', $templates->link);
        $this->assertEquals('<span class="current">{LINK_TEXT}</span>', $templates->currentPage);
        $this->assertEquals('<a href="{LINK_URL}">Next</a>', $templates->nextPage);
        $this->assertEquals('<a href="{LINK_URL}">Prev</a>', $templates->prevPage);
        $this->assertEquals('<a href="{LINK_URL}">First</a>', $templates->firstPage);
        $this->assertEquals('<a href="{LINK_URL}">Last</a>', $templates->lastPage);
        $this->assertEquals('<div class="pagination">{LAYOUT_CONTENT}</div>', $templates->layout);
    }

    public function testIsReadonly(): void
    {
        $templates = new PaginationTemplates();

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        // This should throw an Error because the property is readonly
        $templates->link = 'changed';
    }

    public function testImmutability(): void
    {
        $templates1 = new PaginationTemplates(link: '<a>{LINK_TEXT}</a>');
        $templates2 = new PaginationTemplates(link: '<span>{LINK_TEXT}</span>');

        $this->assertEquals('<a>{LINK_TEXT}</a>', $templates1->link);
        $this->assertEquals('<span>{LINK_TEXT}</span>', $templates2->link);

        // Each instance maintains its own values
        $this->assertNotEquals($templates1->link, $templates2->link);
    }

    public function testPlaceholdersInTemplates(): void
    {
        $templates = new PaginationTemplates();

        // Verify all default templates contain the expected placeholders
        $this->assertStringContainsString('{LINK_URL}', $templates->link);
        $this->assertStringContainsString('{LINK_TEXT}', $templates->link);
        $this->assertStringContainsString('{LINK_URL}', $templates->currentPage);
        $this->assertStringContainsString('{LINK_TEXT}', $templates->currentPage);
        $this->assertStringContainsString('{LINK_URL}', $templates->nextPage);
        $this->assertStringContainsString('{LINK_URL}', $templates->prevPage);
        $this->assertStringContainsString('{LINK_URL}', $templates->firstPage);
        $this->assertStringContainsString('{LINK_URL}', $templates->lastPage);
        $this->assertStringContainsString('{LAYOUT_CONTENT}', $templates->layout);
    }

    public function testBootstrapCompatibility(): void
    {
        $templates = new PaginationTemplates();

        // Verify default templates use Bootstrap classes
        $this->assertStringContainsString('page-item', $templates->link);
        $this->assertStringContainsString('page-link', $templates->link);
        $this->assertStringContainsString('page-item active', $templates->currentPage);
        $this->assertStringContainsString('pagination justify-content-center', $templates->layout);
    }

    public function testNamedParametersOrder(): void
    {
        // Test that parameters can be passed in any order
        $templates = new PaginationTemplates(
            layout: '<div>{LAYOUT_CONTENT}</div>',
            link: '<a>{LINK_TEXT}</a>',
            currentPage: '<span>{LINK_TEXT}</span>',
        );

        $this->assertEquals('<a>{LINK_TEXT}</a>', $templates->link);
        $this->assertEquals('<span>{LINK_TEXT}</span>', $templates->currentPage);
        $this->assertEquals('<div>{LAYOUT_CONTENT}</div>', $templates->layout);
    }
}

