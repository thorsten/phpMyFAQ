<?php

/**
 * Config directory and filesystem path resolution for phpMyFAQ bootstrap
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use Symfony\Component\HttpFoundation\RedirectResponse;

class ConfigDirectoryResolver
{
    /**
     * Defines PMF_CONFIG_DIR and PMF_LEGACY_CONFIG_DIR constants.
     */
    public static function resolve(): void
    {
        if (defined('PMF_MULTI_INSTANCE_CONFIG_DIR')) {
            if (!defined('PMF_CONFIG_DIR')) {
                define('PMF_CONFIG_DIR', PMF_MULTI_INSTANCE_CONFIG_DIR);
            }

            return;
        }

        if (!defined('PMF_CONFIG_DIR')) {
            define('PMF_CONFIG_DIR', PMF_ROOT_DIR . '/content/core/config');
        }

        // For backward compatibility, we also define PMF_LEGACY_CONFIG_DIR if not already defined,
        // but it should not be used by new code.
        // This can be removed if we drop support updates from phpMyFAQ 3.x versions that still use the old config
        // location.
        if (!defined('PMF_LEGACY_CONFIG_DIR')) {
            define('PMF_LEGACY_CONFIG_DIR', PMF_ROOT_DIR . '/config');
        }
    }

    /**
     * Detects the database.php config file. Redirects to /setup/ if missing,
     * and we're not yet in a setup/update context.
     *
     * @return string|null Path to the database.php file, or null if not found (setup redirect sent)
     */
    public static function resolveDatabaseFile(): ?string
    {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $isSetupContext =
            str_contains($requestUri, '/setup/')
            || str_contains($requestUri, '/api/setup/')
            || str_contains($requestUri, '/update')
            || str_contains($requestUri, '/update/');

        $legacyConfigDir = defined('PMF_LEGACY_CONFIG_DIR') ? PMF_LEGACY_CONFIG_DIR : null;

        $configExists = file_exists(PMF_CONFIG_DIR . '/database.php');
        $legacyExists = $legacyConfigDir !== null && file_exists($legacyConfigDir . '/database.php');

        if (!$configExists && !$legacyExists) {
            if (!$isSetupContext) {
                $response = new RedirectResponse('/setup/');
                $response->send();
                exit();
            }

            return null;
        }

        if ($configExists) {
            return PMF_CONFIG_DIR . '/database.php';
        }

        return $legacyConfigDir . '/database.php';
    }

    /**
     * Loads the config-specific constants.php file.
     */
    public static function loadConfigConstants(): void
    {
        if (file_exists(PMF_CONFIG_DIR . '/constants.php')) {
            require_once PMF_CONFIG_DIR . '/constants.php';
            return;
        }

        if (defined('PMF_LEGACY_CONFIG_DIR') && file_exists(PMF_LEGACY_CONFIG_DIR . '/constants.php')) {
            require_once PMF_LEGACY_CONFIG_DIR . '/constants.php';
        }
    }

    /**
     * Resolves the attachments directory with path-traversal protection and defines PMF_ATTACHMENTS_DIR.
     */
    public static function resolveAttachmentsDir(string $confAttachmentsPath, string $rootDir): void
    {
        if (!defined('PMF_ATTACHMENTS_DIR')) {
            define('PMF_ATTACHMENTS_DIR', self::computeAttachmentsPath($confAttachmentsPath, $rootDir));
        }
    }

    /**
     * Computes the resolved attachments path without side effects (no constant definition).
     *
     * @return string|false The resolved path, or false if path traversal was detected
     */
    public static function computeAttachmentsPath(string $confAttachmentsPath, string $rootDir): string|false
    {
        $confAttachmentsPath = trim($confAttachmentsPath);
        if ($confAttachmentsPath[0] === '/' || preg_match('%^[a-z]:[\\\\/]%i', $confAttachmentsPath)) {
            return $confAttachmentsPath;
        }

        $tmp = $rootDir . DIRECTORY_SEPARATOR . $confAttachmentsPath;

        if (str_starts_with($tmp, $rootDir)) {
            return $tmp;
        }

        return false;
    }
}
