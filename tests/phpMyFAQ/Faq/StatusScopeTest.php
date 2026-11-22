<?php

declare(strict_types=1);

namespace phpMyFAQ\Faq;

use PHPUnit\Framework\TestCase;

final class StatusScopeTest extends TestCase
{
    public function testPublishedOnlyRendersAStatusCondition(): void
    {
        $this->assertSame(" AND fd.status = 'published'", StatusScope::publishedOnly()->toSqlFragment());
    }

    public function testTheAliasIsConfigurable(): void
    {
        $this->assertSame(
            " AND faqdata.status = 'published'",
            StatusScope::publishedOnly()->toSqlFragment(faqAlias: 'faqdata'),
        );
    }

    public function testVisibleToReadersAddsThePublicationWindow(): void
    {
        $fragment = StatusScope::visibleToReaders()->toSqlFragment('d');

        $this->assertMatchesRegularExpression(
            "/^ AND d\\.status = 'published' AND d\\.date_start <= '(\\d{14})' AND d\\.date_end >= '\\1'$/",
            $fragment,
        );
    }

    public function testPublishedOnlyLeavesThePublicationWindowOpen(): void
    {
        $this->assertStringNotContainsString('date_start', StatusScope::publishedOnly()->toSqlFragment());
    }

    public function testAnyRendersNothing(): void
    {
        $this->assertSame('', StatusScope::any()->toSqlFragment());
    }
}
