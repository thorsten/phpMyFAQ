<?php

/**
 * Media host allowlist policy for the PDF export image fetcher.
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
 * @since     2026-10-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf;

/**
 * Class MediaHostPolicy
 *
 * Decides whether a URL's origin (scheme, host and port) is covered by the configured media host
 * allowlist. Shared by the Wrapper (which neutralizes disallowed images in the HTML) and the
 * ExternalImageFetcher (which re-checks every redirect hop) so both apply the exact same rules.
 *
 * @package phpMyFAQ\Export\Pdf
 */
final class MediaHostPolicy
{
    /**
     * Checks whether a parsed URL may be fetched: HTTP(S) only, the host must be allowlisted, and
     * the port must be the scheme default unless the matching allowlist entry names that port
     * ("host:port"). Without the port rule an allowlisted host would expose every service listening
     * on that machine to server-side requests.
     *
     * @param array<string, mixed> $parsedUrl    Result of parse_url()
     * @param string[]             $allowedHosts The configured allowlist
     */
    public static function isOriginAllowed(array $parsedUrl, array $allowedHosts): bool
    {
        if (!array_key_exists('host', $parsedUrl)) {
            return false;
        }

        $scheme = strtolower((string) ($parsedUrl['scheme'] ?? 'http'));
        if ($scheme !== 'http' && $scheme !== 'https') {
            return false;
        }

        $entry = self::findAllowedEntry((string) $parsedUrl['host'], $allowedHosts);
        if ($entry === null) {
            return false;
        }

        $defaultPort = $scheme === 'https' ? 443 : 80;
        $port = array_key_exists('port', $parsedUrl) ? (int) $parsedUrl['port'] : $defaultPort;

        return $port === ($entry['port'] ?? $defaultPort);
    }

    /**
     * Lower-cases a host and strips the brackets of an IPv6 literal, so that "[::1]" from
     * parse_url() and "::1" from configuration compare equal.
     */
    public static function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_starts_with($host, '[') && str_ends_with($host, ']')) {
            $host = substr($host, offset: 1, length: -1);
        }

        return rtrim($host, characters: '.');
    }

    /**
     * Finds the allowlist entry covering the given host. Matches an exact hostname or any
     * subdomain of an allowed host; empty entries and the disabled sentinel "0" are ignored.
     *
     * @param string[] $allowedHosts
     * @return array{host: string, port: int|null}|null The matching entry, or null
     */
    private static function findAllowedEntry(string $host, array $allowedHosts): ?array
    {
        $host = self::normalizeHost($host);
        if ($host === '') {
            return null;
        }

        foreach ($allowedHosts as $allowedHost) {
            $entry = self::parseAllowedEntry($allowedHost);
            if ($entry === null) {
                continue;
            }

            if ($host === $entry['host'] || str_ends_with($host, '.' . $entry['host'])) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * Parses one allowlist entry ("host", "host:port" or "[v6]:port") into its host and optional
     * port. Empty entries and the disabled sentinel "0" yield null.
     *
     * @return array{host: string, port: int|null}|null
     */
    private static function parseAllowedEntry(string $entry): ?array
    {
        $entry = strtolower(trim($entry));
        if ($entry === '' || $entry === '0') {
            return null;
        }

        $port = null;
        $matches = [];
        if (preg_match('/^(\[[0-9a-f:.]+\]|[^:\[\]]+):(\d{1,5})$/', $entry, $matches)) {
            $entry = $matches[1];
            $port = (int) $matches[2];
            if ($port < 1 || $port > 65_535) {
                return null;
            }
        }

        $host = self::normalizeHost($entry);

        return $host === '' ? null : ['host' => $host, 'port' => $port];
    }
}
