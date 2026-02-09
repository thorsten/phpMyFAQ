<?php

/**
 * Tenant-scoped storage wrapper.
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
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Storage;

final readonly class TenantScopedStorage implements StorageInterface
{
    public function __construct(
        private StorageInterface $storage,
        private string $tenantPrefix,
    ) {
    }

    public function put(string $path, string $contents): bool
    {
        return $this->storage->put($this->prefixPath($path), $contents);
    }

    public function putStream(string $path, mixed $stream): bool
    {
        return $this->storage->putStream($this->prefixPath($path), $stream);
    }

    public function get(string $path): string
    {
        return $this->storage->get($this->prefixPath($path));
    }

    public function delete(string $path): bool
    {
        return $this->storage->delete($this->prefixPath($path));
    }

    public function exists(string $path): bool
    {
        return $this->storage->exists($this->prefixPath($path));
    }

    public function url(string $path): string
    {
        return $this->storage->url($this->prefixPath($path));
    }

    public function size(string $path): int
    {
        return $this->storage->size($this->prefixPath($path));
    }

    private function prefixPath(string $path): string
    {
        $normalizedPath = ltrim(str_replace('\\', '/', trim($path)), '/');
        $prefix = trim($this->tenantPrefix, '/');

        if ($normalizedPath === '') {
            return $prefix;
        }

        return $prefix . '/' . $normalizedPath;
    }
}
