<?php

/**
 * Trusted proxy configuration for phpMyFAQ bootstrap
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
 * @since     2026-09-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use Symfony\Component\HttpFoundation\Request;

/**
 * Applies the TRUSTED_PROXIES environment variable to the Symfony Request.
 *
 * Without trusted proxies, Symfony ignores the X-Forwarded-* headers, so the
 * client IP, host, port and scheme come from the direct TCP peer. Behind a
 * reverse proxy the operator lists the proxy IPs or CIDR ranges (or the
 * literal "REMOTE_ADDR" to trust the immediate peer), and only then are the
 * forwarded headers honoured.
 */
final class TrustedProxyConfigurator
{
    public const string ENVIRONMENT_VARIABLE = 'TRUSTED_PROXIES';

    public const int TRUSTED_HEADER_SET =
        Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO;

    /**
     * Reads TRUSTED_PROXIES from $_ENV (populated by the .env file) with a
     * fallback to the process environment, and configures the Request class.
     */
    public static function configureFromEnvironment(): void
    {
        $value = $_ENV[self::ENVIRONMENT_VARIABLE] ?? getenv(self::ENVIRONMENT_VARIABLE);

        self::configure(is_string($value) ? $value : '');
    }

    /**
     * @param string $trustedProxies Comma-separated IPs, CIDR ranges or "REMOTE_ADDR"
     */
    public static function configure(string $trustedProxies): void
    {
        $proxies = self::parse($trustedProxies);

        if ($proxies === []) {
            return;
        }

        Request::setTrustedProxies(
            $proxies,
            Request::HEADER_X_FORWARDED_FOR
            | Request::HEADER_X_FORWARDED_HOST
            | Request::HEADER_X_FORWARDED_PORT
            | Request::HEADER_X_FORWARDED_PROTO,
        );
    }

    /**
     * @return string[]
     */
    public static function parse(string $trustedProxies): array
    {
        $entries = array_map(trim(...), explode(',', $trustedProxies));

        return array_values(array_filter(
            $entries,
            static fn(string $entry): bool => $entry !== '' && self::isValidEntry($entry),
        ));
    }

    private static function isValidEntry(string $entry): bool
    {
        if ($entry === 'REMOTE_ADDR') {
            return true;
        }

        $address = $entry;
        $prefixLength = null;
        if (str_contains($entry, '/')) {
            [$address, $prefix] = explode('/', $entry, limit: 2);
            if ($prefix === '' || !ctype_digit($prefix)) {
                return false;
            }

            $prefixLength = (int) $prefix;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $prefixLength === null || $prefixLength <= 32;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            return $prefixLength === null || $prefixLength <= 128;
        }

        return false;
    }
}
