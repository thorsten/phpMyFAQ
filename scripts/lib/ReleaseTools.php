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

final class ReleaseTools
{
    /**
     * Returns the bullet list under "### phpMyFAQ v<version> - <date|unreleased>".
     *
     * @throws InvalidArgumentException when the version has no CHANGELOG section
     */
    public static function extractChangelogSection(string $changelog, string $version): string
    {
        $pattern = '/^### phpMyFAQ v' . preg_quote($version, '/') . ' - .*$/m';

        if (preg_match($pattern, $changelog, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            throw new InvalidArgumentException(
                sprintf('CHANGELOG.md has no section for version %s — add it before releasing.', $version),
            );
        }

        $start = $matches[0][1] + strlen($matches[0][0]);
        $rest = substr($changelog, $start);
        $nextHeading = strpos($rest, "\n### ");

        return trim($nextHeading === false ? $rest : substr($rest, 0, $nextHeading));
    }
}
