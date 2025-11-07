<?php

declare(strict_types=1);

/**
 * Class for filesystem operations.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2012-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2012-04-02
 */

namespace phpMyFAQ\Filesystem;

use phpMyFAQ\Core\Exception;

class Filesystem
{
    private readonly string $rootPath;

    private string $path;

    /**
     * Constructor sets the root path of the primary phpMyFAQ installation.
     */
    public function __construct(string $rootPath = '')
    {
        $this->rootPath = $rootPath === '' || $rootPath === '0' ? dirname(__DIR__, levels: 2) : $rootPath;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Recursively copies the source to the destination.
     * @throws Exception
     */
    public function recursiveCopy(string $source, string $dest): bool
    {
        if (!is_dir($source)) {
            return false;
        }

        $directoryName = substr($source, strrpos($source, needle: '/') + 1);
        $this->createDirectory($dest . DIRECTORY_SEPARATOR . $directoryName, mode: 0o750, recursive: true);

        $entries = scandir($source);
        if ($entries === false) {
            return false;
        }

        foreach (array_diff($entries, ['.', '..']) as $file) {
            $sourcePath = $source . DIRECTORY_SEPARATOR . $file;
            $targetPath = $dest . DIRECTORY_SEPARATOR . $directoryName . DIRECTORY_SEPARATOR . $file;

            if (is_link($sourcePath)) {
                continue;
            }

            if (is_dir($sourcePath)) {
                $this->recursiveCopy($sourcePath, $dest . DIRECTORY_SEPARATOR . $directoryName);
                continue;
            }

            // Default: try to copy, copy() validates readability and destination
            $this->copy($sourcePath, $targetPath);
        }

        return true;
    }

    /**
     * Creates directory.
     */
    public function createDirectory(string $pathname, int $mode = 0o777, bool $recursive = false): bool
    {
        if (is_dir($pathname)) {
            return true;
        }

        return mkdir($pathname, $mode, $recursive);
    }

    /**
     * Moves a given directory.
     */
    public function moveDirectory(string $sourcePath, string $destinationPath): bool
    {
        return rename($sourcePath, $destinationPath);
    }

    /**
     * Deletes the given directory.
     */
    public function deleteDirectory(string $pathname): bool
    {
        // Guard for empty or invalid paths
        if ($pathname === '' || $pathname === '0' || !is_dir($pathname)) {
            return false;
        }

        $entries = scandir($pathname);
        if ($entries === false) {
            return false;
        }

        foreach (array_diff($entries, ['.', '..']) as $file) {
            $full = $pathname . DIRECTORY_SEPARATOR . $file;

            if (is_dir($full) && !is_link($full)) {
                $this->deleteDirectory($full);
                continue;
            }
            unlink($full);
        }

        return rmdir($pathname);
    }

    /**
     * Copies the source file to the destination file.
     *
     * @throws Exception
     */
    public function copy(string $sourceFileName, string $destinationFileName): bool
    {
        if (!is_readable($sourceFileName) || !is_writable(dirname($destinationFileName))) {
            throw new Exception(message: 'Source not readable or destination directory not writeable.');
        }

        if (copy($sourceFileName, $destinationFileName) === false) {
            $error = error_get_last();
            throw new Exception($error['message']);
        }

        return true;
    }
}
