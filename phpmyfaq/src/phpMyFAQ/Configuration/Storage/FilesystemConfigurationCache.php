<?php

/**
 * Filesystem-backed configuration cache
 *
 * Caches the faqconfig rows in the PSR-6 filesystem pool so installs without Redis
 * do not scan the full configuration table on every request. Writes through the
 * HybridConfigurationStore invalidate the cache; a short TTL bounds staleness for
 * multi-server setups where another node changes the configuration.
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

namespace phpMyFAQ\Configuration\Storage;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

final class FilesystemConfigurationCache
{
    private const string CACHE_KEY = 'configuration-rows';

    private const int DEFAULT_TTL = 300;

    private readonly FilesystemAdapter $adapter;

    /**
     * @param string $identity Distinguishes tenants sharing one cache directory
     *                         (multisite config dir, table prefix, table name).
     */
    public function __construct(string $cacheDir, string $identity, int $ttl = self::DEFAULT_TTL)
    {
        $this->adapter = new FilesystemAdapter(
            namespace: 'pmf-config-' . substr(md5($identity), offset: 0, length: 12),
            defaultLifetime: $ttl,
            directory: $cacheDir,
        );
    }

    /**
     * Returns null (disabled) in debug mode or when CONFIG_CACHE_ENABLED is falsy.
     */
    public static function createIfEnabled(bool $debug, mixed $enabled, string $cacheDir, string $identity): ?self
    {
        if ($debug) {
            return null;
        }

        if (!filter_var($enabled ?? 'true', FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        return new self($cacheDir, $identity);
    }

    /**
     * Returns the cached configuration rows, or null on a cache miss.
     *
     * @return array<int, object>|null
     */
    public function read(): ?array
    {
        $item = $this->adapter->getItem(self::CACHE_KEY);
        if (!$item->isHit()) {
            return null;
        }

        /** @var mixed $payload */
        $payload = $item->get();
        if (!is_array($payload) || $payload === []) {
            return null;
        }

        $rows = [];
        /** @var mixed $entry */
        foreach ($payload as $entry) {
            if (!is_array($entry) || !array_key_exists('config_name', $entry) || $entry['config_name'] === null) {
                return null;
            }

            $rows[] = (object) [
                'config_name' => (string) $entry['config_name'],
                'config_value' => (string) ($entry['config_value'] ?? ''),
            ];
        }

        return $rows;
    }

    /**
     * @param array<int, object> $rows
     */
    public function warm(array $rows): void
    {
        $payload = [];
        foreach ($rows as $row) {
            if (!property_exists($row, 'config_name') || $row->config_name === null) {
                continue;
            }

            $payload[] = [
                'config_name' => (string) $row->config_name,
                'config_value' => (string) ($row->config_value ?? ''),
            ];
        }

        if ($payload === []) {
            return;
        }

        $item = $this->adapter->getItem(self::CACHE_KEY);
        $item->set($payload);
        $this->adapter->save($item);
    }

    public function clear(): void
    {
        $this->adapter->deleteItem(self::CACHE_KEY);
    }
}
