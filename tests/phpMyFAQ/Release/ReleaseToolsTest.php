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
}
