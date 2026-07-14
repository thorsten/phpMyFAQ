<?php

/**
 * Twig extension to append a cache-busting version to built asset URLs
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Template
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-07-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig\Extensions;

use Twig\Attribute\AsTwigFunction;
use Twig\Extension\AbstractExtension;

/**
 * Class AssetVersionTwigExtension
 *
 * Appends the asset file's modification time as a ?v= query parameter, so
 * browsers re-fetch scripts and stylesheets after an upgrade instead of
 * serving stale cached copies, while unchanged files stay cacheable.
 */
class AssetVersionTwigExtension extends AbstractExtension
{
    #[AsTwigFunction(name: 'asset')]
    public static function asset(string $path): string
    {
        return self::versionedPath($path, (string) PMF_ROOT_DIR);
    }

    public static function versionedPath(string $path, string $rootDir): string
    {
        if (str_contains($path, '..')) {
            return $path;
        }

        $file = rtrim(string: $rootDir, characters: '/') . '/' . ltrim(string: $path, characters: '/');
        if (!is_file($file)) {
            return $path;
        }

        $modificationTime = filemtime($file);
        if ($modificationTime === false) {
            return $path;
        }

        return $path . '?v=' . $modificationTime;
    }
}
