<?php

namespace phpMyFAQ;

use PHPUnit\Framework\TestCase;

class PaginationTest extends TestCase
{
    public function testRender(): void
    {
        $pagination = new Pagination([
            'total' => 30,
            'perPage' => 10,
            'baseUrl' => 'http://example.com?action=foo'
        ]);

        $expectedOutput =
            '<ul class="pagination justify-content-center">' .
            '<li class="page-item active">' .
            '<a class="page-link" href="http://example.com?action=foo&amp;page=1">1</a>' .
            '</li>&nbsp;&nbsp;<li class="page-item">' .
            '<a class="page-link" href="http://example.com?action=foo&amp;page=2">2</a>' .
            '</li>&nbsp;&nbsp;<li class="page-item">' .
            '<a class="page-link" href="http://example.com?action=foo&amp;page=3">3</a>' .
            '</li>&nbsp;&nbsp;<li class="page-item">' .
            '<a class="page-link" href="http://example.com?action=foo&amp;page=2">&rarr;</a>' .
            '</li>&nbsp;&nbsp;<li class="page-item">' .
            '<a class="page-link" href="http://example.com?action=foo&amp;page=3">&#8677;</a>' .
            '</li></ul>';

        $this->assertEquals($expectedOutput, $pagination->render());
    }
}
