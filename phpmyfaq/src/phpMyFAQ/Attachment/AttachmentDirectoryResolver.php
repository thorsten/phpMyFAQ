<?php

/**
 * Resolves the directory in which attachment files are stored.
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
 * @since     2026-11-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Attachment;

/**
 * Class AttachmentDirectoryResolver
 *
 * Unencrypted attachments are stored under a path derived only from the MD5
 * hash of the file. Client instances of a multisite installation inherit the
 * master's "records.attachmentsPath", so without a per-instance namespace all
 * instances would share one blob store, and deleting an attachment in one
 * instance would remove the file of every other instance holding the same
 * bytes. Client instances therefore get their own sub-directory, named after
 * their multisite configuration folder (the client hostname).
 *
 * @package phpMyFAQ\Attachment
 */
final class AttachmentDirectoryResolver
{
    /**
     * Returns the absolute attachment directory, or false if the configured
     * relative path escapes the phpMyFAQ root directory.
     *
     * @param string      $configuredPath       Value of "records.attachmentsPath"
     * @param string      $rootDir              Absolute phpMyFAQ root directory
     * @param string|null $instanceConfigDir    PMF_MULTI_INSTANCE_CONFIG_DIR of a client instance, null for the master
     */
    public static function resolve(
        string $configuredPath,
        string $rootDir,
        ?string $instanceConfigDir = null,
    ): string|false {
        $configuredPath = trim($configuredPath);
        $rootDir = rtrim($rootDir, characters: '/\\');

        $isAbsolutePath =
            '' !== $configuredPath && ('/' === $configuredPath[0] || preg_match('%^[a-z]:[\\\\/]%i', $configuredPath));

        if ($isAbsolutePath) {
            // Windows or Unix style absolute path
            return self::withInstanceNamespace(rtrim($configuredPath, characters: '/\\'), $instanceConfigDir);
        }

        $attachmentsDir = $rootDir . DIRECTORY_SEPARATOR . $configuredPath;

        // Check that nobody is traversing
        $pathSegments = preg_split('%[\\\\/]+%', $configuredPath);
        $pathSegments = $pathSegments === false ? [] : $pathSegments;
        if (!str_starts_with($attachmentsDir, $rootDir) || in_array('..', $pathSegments, strict: true)) {
            return false;
        }

        return self::withInstanceNamespace($attachmentsDir, $instanceConfigDir);
    }

    private static function withInstanceNamespace(string $attachmentsDir, ?string $instanceConfigDir): string
    {
        $namespace = self::instanceNamespace($instanceConfigDir);
        if (null === $namespace) {
            return $attachmentsDir;
        }

        return $attachmentsDir . DIRECTORY_SEPARATOR . 'multisite' . DIRECTORY_SEPARATOR . $namespace;
    }

    /**
     * Returns the namespace of a client instance, or null for the master instance.
     */
    public static function instanceNamespace(?string $instanceConfigDir): ?string
    {
        if (null === $instanceConfigDir) {
            return null;
        }

        $namespace = basename(rtrim($instanceConfigDir, characters: '/\\'));

        // Only allow characters that are valid in a hostname, so the namespace cannot traverse
        if ('' === $namespace || !preg_match('/^[a-z0-9][a-z0-9.-]*$/i', $namespace)) {
            return null;
        }

        return strtolower($namespace);
    }
}
