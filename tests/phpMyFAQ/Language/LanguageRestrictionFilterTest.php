<?php

declare(strict_types=1);

namespace phpMyFAQ\Language;

use PHPUnit\Framework\TestCase;

final class LanguageRestrictionFilterTest extends TestCase
{
    /** @var array<string, string> */
    private array $languages = [
        'en' => 'English',
        'de' => 'Deutsch',
        'fr' => 'Français',
    ];

    public function testNullMeansUnrestricted(): void
    {
        $this->assertSame($this->languages, LanguageRestrictionFilter::filter($this->languages, null));
    }

    public function testKeepsOnlyAllowedLanguages(): void
    {
        $filtered = LanguageRestrictionFilter::filter($this->languages, ['en', 'fr']);
        $this->assertSame(['en', 'fr'], array_keys($filtered));
    }

    public function testEmptyAllowListHidesEverything(): void
    {
        $this->assertSame([], LanguageRestrictionFilter::filter($this->languages, []));
    }

    public function testExcludedLanguagesIsEmptyWhenUnrestricted(): void
    {
        $this->assertSame([], LanguageRestrictionFilter::excludedLanguages($this->languages, null));
    }

    public function testExcludedLanguagesIsComplementOfAllowedSet(): void
    {
        $excluded = LanguageRestrictionFilter::excludedLanguages($this->languages, ['en', 'fr']);
        $this->assertSame(['de'], $excluded);
    }

    public function testExcludedLanguagesExcludesEverythingForEmptyAllowList(): void
    {
        $excluded = LanguageRestrictionFilter::excludedLanguages($this->languages, []);
        $this->assertSame(['en', 'de', 'fr'], $excluded);
    }
}
