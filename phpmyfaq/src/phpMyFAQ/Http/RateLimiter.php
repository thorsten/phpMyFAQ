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

        $selectQuery = sprintf(
            "SELECT requests FROM %s WHERE rate_key = '%s' AND window_start = %d",
            $table,
            $escapedKey,
            $windowStart,
        );

        $result = $db->query($selectQuery);
        $row = $result === false ? false : $db->fetchObject($result);
        $currentRequests = is_object($row) && isset($row->requests) ? (int) $row->requests : 0;

        if ($currentRequests >= $limit) {
            $this->headersStorage = [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $windowReset,
                'Retry-After' => max(1, $windowReset - $now),
            ];

            return false;
        }

        if ($currentRequests === 0) {
            $insertQuery = sprintf(
                "INSERT INTO %s (rate_key, window_start, requests, created) VALUES ('%s', %d, 1, %s)",
                $table,
                $escapedKey,
                $windowStart,
                $db->now(),
            );
            $db->query($insertQuery);
            $remaining = $limit - 1;
        } else {
            $updatedRequests = $currentRequests + 1;
            $updateQuery = sprintf(
                "UPDATE %s SET requests = %d WHERE rate_key = '%s' AND window_start = %d",
                $table,
                $updatedRequests,
                $escapedKey,
                $windowStart,
            );
            $db->query($updateQuery);
            $remaining = max(0, $limit - $updatedRequests);
        }

        $this->headersStorage = [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => $remaining,
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
