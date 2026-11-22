<?php

/**
 * Reads the phpMyFAQ version from a System.php file on disk.
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
 * @since     2026-11-21
 */

declare(strict_types=1);

namespace phpMyFAQ\Setup;

/**
 * The command line update replaces the files and migrates the database in one process. The
 * System class of that process was loaded before the files were replaced, so it still reports
 * the version that was installed before. The file on disk is the only place that knows the
 * version of the code that is installed now.
 */
final readonly class SystemFileVersion
{
    private string $source;

    public function __construct(string $systemFile)
    {
        $source = is_file($systemFile) ? file_get_contents($systemFile) : false;
        $this->source = $source === false ? '' : $source;
    }

    /**
     * Returns the version as System::getVersion() builds it, or null when the file could
     * not be read or does not declare the version constants.
     */
    public function version(): ?string
    {
        $major = $this->constant('VERSION_MAJOR');
        $minor = $this->constant('VERSION_MINOR');
        $patchLevel = $this->constant('VERSION_PATCH_LEVEL');
        $preRelease = $this->constant('VERSION_PRE_RELEASE');

        if ($major === null || $minor === null || $patchLevel === null || $preRelease === null) {
            return null;
        }

        $version = $major . '.' . $minor . '.' . $patchLevel;

        return $preRelease === '' ? $version : $version . '-' . $preRelease;
    }

    public function apiVersion(): ?string
    {
        return $this->constant('VERSION_API');
    }

    private function constant(string $name): ?string
    {
        $matches = [];
        $pattern = '/\bconst\s+(?:int\s+|string\s+)?' . $name . '\s*=\s*(?:(\d+)|\'([^\']*)\')\s*;/';

        if (preg_match($pattern, $this->source, $matches, PREG_UNMATCHED_AS_NULL) !== 1) {
            return null;
        }

        return $matches[1] ?? $matches[2] ?? null;
    }
}
