<?php

/**
 * Resolves the Twig compile-cache directory from the environment.
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
 * @since     2026-07-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Twig;

/**
 * Class TwigCacheResolver
 *
 * Decides whether compiled Twig templates are cached and where, mirroring
 * the route-cache configuration: enabled by default in production, disabled
 * in debug mode, and overridable via TWIG_CACHE_ENABLED / TWIG_CACHE_DIR.
 */
final class TwigCacheResolver
{
    /**
     * @return string|false the cache directory, or false when caching is disabled
     */
    public static function resolve(
        bool $debug,
        mixed $enabled,
        ?string $configuredDir,
        string $defaultDir,
    ): string|false {
        if ($debug) {
            return false;
        }

        if (!filter_var($enabled ?? 'true', FILTER_VALIDATE_BOOLEAN)) {
            return false;
        }

        if ($configuredDir !== null && trim($configuredDir) !== '') {
            return $configuredDir;
        }

        return $defaultDir;
    }
}
