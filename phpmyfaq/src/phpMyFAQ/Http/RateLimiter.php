<?php

/**
 * API rate limiter using symfony/rate-limiter.
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
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

final class RateLimiter
{
    /** @var array<string, int|string> */
    private array $headersStorage = [];

    /** @var array<string, int|string> */
    public array $headers {
        get => $this->headersStorage;
    }

    private readonly StorageInterface $storage;

    public function __construct(
        private readonly Configuration $configuration,
        ?StorageInterface $storage = null,
    ) {
        $this->storage = $storage ?? new InMemoryStorage();
    }

    /**
     * Checks if a request should be allowed for the given key.
     */
    public function check(string $key, int $limit, int $intervalSeconds): bool
    {
        $limit = max(1, $limit);
        $intervalSeconds = max(1, $intervalSeconds);

        $factory = new RateLimiterFactory(config: [
            'id' => 'api',
            'policy' => 'fixed_window',
            'limit' => $limit,
            'interval' => $intervalSeconds . ' seconds',
        ], storage: $this->storage);

        $limiter = $factory->create($key);
        $rateLimit = $limiter->consume(1);

        $resetTime = $rateLimit->getRetryAfter()->getTimestamp();

        if ($rateLimit->isAccepted()) {
            $this->headersStorage = [
                'X-RateLimit-Limit' => $limit,
                'X-RateLimit-Remaining' => $rateLimit->getRemainingTokens(),
                'X-RateLimit-Reset' => $resetTime,
            ];

            return true;
        }

        $this->headersStorage = [
            'X-RateLimit-Limit' => $limit,
            'X-RateLimit-Remaining' => 0,
            'X-RateLimit-Reset' => $resetTime,
            'Retry-After' => max(1, $resetTime - time()),
        ];

        return false;
    }

    /**
     * @return array<string, int|string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
