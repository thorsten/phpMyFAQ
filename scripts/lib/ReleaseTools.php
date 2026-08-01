<?php

/**
 * Release tooling helpers: changelog extraction and news draft generation.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-01
 */

declare(strict_types=1);

namespace phpMyFAQ\Release;

use InvalidArgumentException;

/**
 * Class ReleaseTools
 *
 * @package phpMyFAQ\Release
 */
final class ReleaseTools
{
    /**
     * Returns the bullet list under "### phpMyFAQ v<version> - <date|unreleased>".
     *
     * @throws InvalidArgumentException when the version has no CHANGELOG section
     */
    public static function extractChangelogSection(string $changelog, string $version): string
    {
        $pattern = '/^### phpMyFAQ v' . preg_quote(str: $version, delimiter: '/') . ' - .*$/m';

        if (preg_match($pattern, $changelog, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            throw new InvalidArgumentException(
                sprintf('CHANGELOG.md has no section for version %s — add it before releasing.', $version),
            );
        }

        $start = $matches[0][1] + strlen($matches[0][0]);
        $rest = substr($changelog, $start);
        $nextHeading = strpos(haystack: $rest, needle: "\n### ");

        return trim($nextHeading === false ? $rest : substr(string: $rest, offset: 0, length: $nextHeading));
    }

    /**
     * Renders a news entry draft in the format used by content/news/<year>.md
     * on www.phpmyfaq.de. The changelog section is embedded as an HTML comment
     * for the maintainer to consult while editing the announcement text.
     */
    public static function newsDraft(
        string $version,
        string $date,
        string $changelogSection,
        string $codename = 'TBD',
    ): string {
        return implode("\n", [
            sprintf('### %s', $date),
            '',
            sprintf(
                'The phpMyFAQ Team is pleased to announce [phpMyFAQ %s](/download), the "%s" release.',
                $version,
                $codename,
            ),
            'This release updates all third party dependencies, and fixes all reported bugs.',
            '',
            '<!-- CHANGELOG for editing reference — remove before publishing:',
            $changelogSection,
            '-->',
        ]) . "\n";
    }

    /**
     * Inserts a news entry directly after the YAML frontmatter block.
     *
     * @throws InvalidArgumentException when the file has no leading frontmatter
     */
    public static function insertNewsEntry(string $newsFileContent, string $entry): string
    {
        $delimiter = "\n---\n";
        $frontmatterEnd = strpos(haystack: $newsFileContent, needle: $delimiter);

        if (!str_starts_with($newsFileContent, "---\n") || $frontmatterEnd === false) {
            throw new InvalidArgumentException(
                'News file has no YAML frontmatter — expected a leading "---" block.',
            );
        }

        $head = substr($newsFileContent, offset: 0, length: $frontmatterEnd + strlen($delimiter));
        $tail = ltrim(substr($newsFileContent, offset: $frontmatterEnd + strlen($delimiter)), characters: "\n");

        return $head . "\n" . rtrim($entry) . "\n\n" . $tail;
    }
}
