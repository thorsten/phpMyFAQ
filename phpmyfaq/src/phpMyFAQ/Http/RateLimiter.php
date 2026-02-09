<?php

/**
 * API rate limiter.
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

namespace phpMyFAQ\Http;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;

final class RateLimiter
{
    /** @var array<string, int|string> */
    private array $headersStorage = [];

    /** @var array<string, int|string> */
    public array $headers {
        get => $this->headersStorage;
    }

    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Checks if a request should be allowed for the given key.
     */
    public function check(string $key, int $limit, int $intervalSeconds): bool
    {
        $limit = max(1, $limit);
        $intervalSeconds = max(1, $intervalSeconds);

        $db = $this->configuration->getDb();
        $escapedKey = $db->escape($key);
        $table = Database::getTablePrefix() . 'faqrate_limits';

        $now = time();
        $windowStart = (int) (floor($now / $intervalSeconds) * $intervalSeconds);
        $windowReset = $windowStart + $intervalSeconds;

        // Attempt INSERT for a new window (atomic — either succeeds or fails on duplicate key)
        $insertQuery = sprintf(
            "INSERT INTO %s (rate_key, window_start, requests, created) VALUES ('%s', %d, 1, %s)",
            $table,
            $escapedKey,
            $windowStart,
            $db->now(),
        );

        if ($db->query($insertQuery) === false) {
            // Row already exists — atomically increment using the DB's current value
            $updateQuery = sprintf(
                "UPDATE %s SET requests = requests + 1 WHERE rate_key = '%s' AND window_start = %d",
                $table,
                $escapedKey,
                $windowStart,
            );

            if ($db->query($updateQuery) === false) {
                // DB write failed — deny the request (fail-closed)
                $this->headersStorage = [
                    'X-RateLimit-Limit' => $limit,
                    'X-RateLimit-Remaining' => 0,
                    'X-RateLimit-Reset' => $windowReset,
                    'Retry-After' => max(1, $windowReset - $now),
                ];

                return false;
            }
        }

        // Read the authoritative post-increment count
        $selectQuery = sprintf(
            "SELECT requests FROM %s WHERE rate_key = '%s' AND window_start = %d",
            $table,
            $escapedKey,
            $windowStart,
        );
        $result = $db->query($selectQuery);
        $row = $result !== false ? $db->fetchObject($result) : false;

        if (!is_object($row) || !isset($row->requests)) {
            // Cannot read authoritative count — deny the request (fail-closed)
            $this->headersStorage = [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $windowReset,
                'Retry-After' => max(1, $windowReset - $now),
            ];

            return false;
        }

        $currentRequests = (int) $row->requests;

        if ($currentRequests > $limit) {
            $this->headersStorage = [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $windowReset,
                'Retry-After' => max(1, $windowReset - $now),
            ];

            return false;
        }

        $this->headersStorage = [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => max(0, $limit - $currentRequests),
            'X-RateLimit-Reset' => $windowReset,
        ];

        return true;
    }

    /**
     * @return array<string, int|string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
