<?php

declare(strict_types=1);

namespace phpMyFAQ\Release;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../scripts/lib/ReleaseTools.php';

/**
 * Class ReleaseToolsTest
 *
 * @package phpMyFAQ\Release
 */
class ReleaseToolsTest extends TestCase
{
    private const string CHANGELOG = <<<'MD'
# phpMyFAQ 4.2

**Codename "Palaimon"**

## CHANGELOG

### phpMyFAQ v4.2.0-alpha - unreleased

- changed PHP requirement to PHP 8.4 or later (Thorsten)
- added Symfony Router for frontend (Thorsten)

### phpMyFAQ v4.1.6 - 2026-07-13

- fixed security vulnerabilities (Thorsten)
- updated third party dependencies (Thorsten)

### phpMyFAQ v4.1.5 - 2026-06-14

- fixed bugs (Thorsten)
MD;

    public function testExtractsSectionForReleasedVersion(): void
    {
        $section = ReleaseTools::extractChangelogSection(self::CHANGELOG, '4.1.6');

        $this->assertSame(
            "- fixed security vulnerabilities (Thorsten)\n- updated third party dependencies (Thorsten)",
            $section,
        );
    }

    public function testExtractsSectionForPrereleaseVersion(): void
    {
        $section = ReleaseTools::extractChangelogSection(self::CHANGELOG, '4.2.0-alpha');

        $this->assertSame(
            "- changed PHP requirement to PHP 8.4 or later (Thorsten)\n- added Symfony Router for frontend (Thorsten)",
            $section,
        );
    }

    public function testExtractsLastSectionWithoutFollowingHeading(): void
    {
        $section = ReleaseTools::extractChangelogSection(self::CHANGELOG, '4.1.5');

        $this->assertSame('- fixed bugs (Thorsten)', $section);
    }

    public function testThrowsWhenVersionHasNoSection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CHANGELOG.md has no section for version 9.9.9');

        ReleaseTools::extractChangelogSection(self::CHANGELOG, '9.9.9');
    }

    public function testDoesNotMatchVersionPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);

        // '4.1' must not match the '4.1.6' heading
        ReleaseTools::extractChangelogSection(self::CHANGELOG, '4.1');
    }

    public function testNewsDraftContainsAnnouncementAndChangelogComment(): void
    {
        $draft = ReleaseTools::newsDraft('4.1.7', '2026-08-15', '- fixed bugs (Thorsten)', 'Jane Doe');

        $expected = <<<'MD'
### 2026-08-15

The phpMyFAQ Team is pleased to announce [phpMyFAQ 4.1.7](/download), the "Jane Doe" release.
This release updates all third party dependencies, and fixes all reported bugs.

<!-- CHANGELOG for editing reference — remove before publishing:
- fixed bugs (Thorsten)
-->

MD;
        $this->assertSame($expected, $draft);
    }

    public function testNewsDraftDefaultsCodenameToTbd(): void
    {
        $draft = ReleaseTools::newsDraft('4.1.7', '2026-08-15', '- fixed bugs (Thorsten)');

        $this->assertStringContainsString('the "TBD" release', $draft);
    }

    public function testInsertNewsEntryPlacesEntryAfterFrontmatter(): void
    {
        $newsFile = <<<'MD'
---
title: phpMyFAQ News from 2026
canonical: news/2026
---

### 2026-07-13

Old entry.
MD;

        $result = ReleaseTools::insertNewsEntry($newsFile, "### 2026-08-15\n\nNew entry.\n");

        $expected = <<<'MD'
---
title: phpMyFAQ News from 2026
canonical: news/2026
---

### 2026-08-15

New entry.

### 2026-07-13

Old entry.
MD;
        $this->assertSame($expected, $result);
    }

    public function testInsertNewsEntryThrowsWithoutFrontmatter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ReleaseTools::insertNewsEntry("### 2026-07-13\n\nNo frontmatter here.\n", "entry\n");
    }
}
