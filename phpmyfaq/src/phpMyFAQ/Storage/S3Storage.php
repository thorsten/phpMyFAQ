<?php

/**
 * S3 storage implementation.
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

use Throwable;

final readonly class S3Storage implements StorageInterface
{
    public function __construct(
        private object $client,
        private string $bucket,
        private string $prefix = '',
        private ?string $publicBaseUrl = null,
    ) {
    }

    public function put(string $path, string $contents): bool
    {
        $key = $this->buildKey($path);
        $this->run(fn(): mixed => $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $contents,
        ]));

        return true;
    }

    public function putStream(string $path, mixed $stream): bool
    {
        if (!is_resource($stream)) {
            throw new StorageException('Stream must be a valid resource.');
        }

        $key = $this->buildKey($path);
        $this->run(fn(): mixed => $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => $stream,
        ]));

        return true;
    }

    public function get(string $path): string
    {
        $key = $this->buildKey($path);
        $result = $this->run(fn(): mixed => $this->client->getObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]));

        if (!isset($result['Body'])) {
            throw new StorageException('Invalid S3 response while reading object: ' . $key);
        }

        return (string) $result['Body'];
    }

    public function delete(string $path): bool
    {
        $key = $this->buildKey($path);
        $this->run(fn(): mixed => $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]));

        return true;
    }

    public function exists(string $path): bool
    {
        $key = $this->buildKey($path);

        return (bool) $this->run(fn(): mixed => $this->client->doesObjectExistV2($this->bucket, $key));
    }

    public function url(string $path): string
    {
        $key = $this->buildKey($path);
        if ($this->publicBaseUrl !== null && $this->publicBaseUrl !== '') {
            return rtrim($this->publicBaseUrl, '/') . '/' . $key;
        }

        $result = $this->run(fn(): mixed => $this->client->getObjectUrl($this->bucket, $key));

        return (string) $result;
    }

    public function size(string $path): int
    {
        $key = $this->buildKey($path);
        $result = $this->run(fn(): mixed => $this->client->headObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]));

        if (!isset($result['ContentLength'])) {
            throw new StorageException('Invalid S3 response while reading object size: ' . $key);
        }

        return (int) $result['ContentLength'];
    }

    /**
     * @param callable(): mixed $callback
     */
    private function run(callable $callback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $throwable) {
            throw new StorageException($throwable->getMessage(), previous: $throwable);
        }
    }

    private function buildKey(string $path): string
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

        if ($this->prefix === '') {
            return $normalizedPath;
        }

        return trim($this->prefix, '/') . '/' . $normalizedPath;
    }
}
