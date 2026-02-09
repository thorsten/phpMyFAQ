<?php

/**
 * Filesystem storage implementation.
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

final readonly class FilesystemStorage implements StorageInterface
{
    private string $rootPath;

    public function __construct(
        string $rootPath,
        private ?string $publicBaseUrl = null,
    ) {
        $this->rootPath = rtrim($rootPath, '/\\');
    }

    public function put(string $path, string $contents): bool
    {
        $fullPath = $this->buildFullPath($path);
        $directory = dirname($fullPath);
        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new StorageException('Unable to create directory for storage path: ' . $directory);
        }

        $result = file_put_contents($fullPath, $contents, LOCK_EX);
        if ($result === false) {
            throw new StorageException('Unable to write file to storage path: ' . $path);
        }

        return true;
    }

    public function putStream(string $path, mixed $stream): bool
    {
        if (!is_resource($stream)) {
            throw new StorageException('Stream must be a valid resource.');
        }

        $fullPath = $this->buildFullPath($path);
        $directory = dirname($fullPath);
        if (!is_dir($directory) && !mkdir($directory, 0o775, true) && !is_dir($directory)) {
            throw new StorageException('Unable to create directory for storage path: ' . $directory);
        }

        $target = fopen($fullPath, 'wb');
        if ($target === false) {
            throw new StorageException('Unable to open file for writing: ' . $path);
        }

        try {
            if (stream_copy_to_stream($stream, $target) === false) {
                throw new StorageException('Unable to write stream contents: ' . $path);
            }
        } finally {
            fclose($target);
        }

        return true;
    }

    public function get(string $path): string
    {
        $fullPath = $this->buildFullPath($path);
        $contents = file_get_contents($fullPath);

        if ($contents === false) {
            throw new StorageException('Unable to read file from storage path: ' . $path);
        }

        return $contents;
    }

    public function delete(string $path): bool
    {
        $fullPath = $this->buildFullPath($path);
        if (!file_exists($fullPath)) {
            return false;
        }

        $result = unlink($fullPath);
        if ($result === false) {
            throw new StorageException('Unable to delete file from storage path: ' . $path);
        }

        return true;
    }

    public function exists(string $path): bool
    {
        return file_exists($this->buildFullPath($path));
    }

    public function url(string $path): string
    {
        $normalizedPath = $this->normalizePathForUrl($path);
        if ($this->publicBaseUrl !== null && $this->publicBaseUrl !== '') {
            return rtrim($this->publicBaseUrl, '/') . '/' . $normalizedPath;
        }

        return str_replace('\\', '/', $this->buildFullPath($path));
    }

    public function size(string $path): int
    {
        $fullPath = $this->buildFullPath($path);
        $size = filesize($fullPath);

        if ($size === false) {
            throw new StorageException('Unable to fetch file size for storage path: ' . $path);
        }

        return $size;
    }

    private function buildFullPath(string $path): string
    {
        return $this->rootPath . DIRECTORY_SEPARATOR . $this->normalizePath($path);
    }

    private function normalizePathForUrl(string $path): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', trim($path)), '/');
        if ($normalizedPath === '') {
            throw new StorageException('Invalid storage path.');
        }

        $segments = explode('/', $normalizedPath);
        foreach ($segments as $segment) {
            if ($segment === '..' || $segment === '') {
                throw new StorageException('Invalid storage path.');
            }
        }

        return $normalizedPath;
    }

    private function normalizePath(string $path): string
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $this->normalizePathForUrl($path));
    }
}
