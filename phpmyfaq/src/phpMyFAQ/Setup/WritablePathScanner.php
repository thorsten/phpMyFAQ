<?php

/**
 * Scans a directory tree for paths the current process cannot write to.
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
 * @since     2026-08-07
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class WritablePathScanner
{
    /**
     * Returns all paths inside the given directory the current process cannot
     * write to. Paths inside the excluded directory are skipped.
     *
     * @return string[]
     */
    public static function getNonWritablePaths(string $directory, string $excludedDirectory): array
    {
        $nonWritablePaths = [];
        $realExcludedDirectory = realpath($excludedDirectory);

        $items = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
            RecursiveIteratorIterator::CATCH_GET_CHILD,
        );

        foreach ($items as $item) {
            $path = $item->getPathname();

            if ($realExcludedDirectory !== false && str_starts_with($path, $realExcludedDirectory)) {
                continue;
            }

            if (!$item->isWritable()) {
                $nonWritablePaths[] = $path;
            }
        }

        return $nonWritablePaths;
    }

    /**
     * Formats a list of paths for an error message, truncated to the first
     * five entries.
     *
     * @param string[] $paths
     */
    public static function formatPathList(array $paths): string
    {
        $additionalPathCount = count($paths) - 5;

        return (
            implode(', ', array_slice($paths, offset: 0, length: 5))
            . ($additionalPathCount > 0 ? sprintf(' and %d more', $additionalPathCount) : '')
        );
    }
}
