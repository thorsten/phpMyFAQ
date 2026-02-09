<?php

/**
 * Storage interface for abstracting file storage operations.
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
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Storage;

/**
 * Interface StorageInterface
 *
 * @package phpMyFAQ\Storage
 */
interface StorageInterface
{
    /**
     * Write string contents to a path.
     *
     * @param string $path     the storage path
     * @param string $contents the file contents
     * @throws StorageException
     */
    public function put(string $path, string $contents): bool;

    /**
     * Write from a stream resource for large files.
     *
     * @param string $path   the storage path
     * @param mixed  $stream a readable stream resource
     * @throws StorageException
     */
    public function putStream(string $path, mixed $stream): bool;

    /**
     * Read the entire file contents.
     *
     * @param string $path the storage path
     * @throws StorageException
     */
    public function get(string $path): string;

    /**
     * Delete a file.
     *
     * @param string $path the storage path
     * @throws StorageException
     */
    public function delete(string $path): bool;

    /**
     * Check if a file exists.
     *
     * @param string $path the storage path
     */
    public function exists(string $path): bool;

    /**
     * Get the public/accessible URL for a file.
     *
     * @param string $path the storage path
     * @throws StorageException
     */
    public function url(string $path): string;

    /**
     * Get file size in bytes.
     *
     * @param string $path the storage path
     * @throws StorageException
     */
    public function size(string $path): int;
}
