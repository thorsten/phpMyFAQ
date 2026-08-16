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

    public function testAnyRendersNothing(): void
    {
        $this->assertSame('', StatusScope::any()->toSqlFragment());
    }
}
