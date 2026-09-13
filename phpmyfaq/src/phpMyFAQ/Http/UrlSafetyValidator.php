<?php

/**
 * Validator for administrator-supplied URLs, DSNs and host names
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

namespace phpMyFAQ\Http;

use Closure;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Checks URLs the server will connect to (SSRF protection), URLs the browser
 * is redirected to, Redis DSNs and host name lists before they are stored in
 * the configuration. The DNS resolver is injectable so tests never hit the
 * network.
 */
final readonly class UrlSafetyValidator
{
    private const array HTTP_SCHEMES = ['http', 'https'];

    private const array REDIS_SCHEMES = ['redis', 'rediss', 'tcp', 'unix'];

    private const string HOSTNAME_PATTERN = '/^(?=.{1,253}$)(?!-)[a-z0-9-]{1,63}(?<!-)(\.(?!-)[a-z0-9-]{1,63}(?<!-))*$/i';

    /** @var Closure(string): list<string> */
    private Closure $resolver;

    /**
     * @param null|Closure(string): list<string> $resolver Returns the IP addresses of a host name
     */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? self::resolveWithDns(...);
    }

    /**
     * A http(s) URL the server itself will request: the host must not resolve
     * to loopback, private or link-local addresses (cloud metadata endpoints,
     * internal services).
     *
     * Administrator-configured services (an identity provider, a translation
     * backend) legitimately live on private networks in container setups, so
     * $allowPrivateNetworks keeps only the link-local and reserved ranges
     * blocked, which is where cloud metadata endpoints live.
     */
    public function isSafeOutboundHttpUrl(string $url, bool $allowPrivateNetworks = false): bool
    {
        $host = $this->httpUrlHost($url);

        return $host !== null && $this->isPublicHost($host, $allowPrivateNetworks);
    }

    /**
     * A http(s) URL the browser is sent to (redirect targets): only the shape
     * is checked, the host may be the phpMyFAQ installation itself.
     */
    public function isHttpUrl(string $url): bool
    {
        return $this->httpUrlHost($url) !== null;
    }

    public function isRedisDsn(string $dsn): bool
    {
        $dsn = trim($dsn);

        // parse_url() rejects "unix:///path" (empty authority), which the Redis handlers accept
        if (preg_match('#^unix:/{0,3}(/[^\s/][^\s]*)$#i', $dsn) === 1) {
            return true;
        }

        $parts = parse_url($dsn);
        if (!is_array($parts)) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        if (!in_array($scheme, self::REDIS_SCHEMES, strict: true) || $scheme === 'unix') {
            return false;
        }

        return ($parts['host'] ?? '') !== '';
    }

    /**
     * Comma-separated host names (no scheme, path or port).
     */
    public function isHostnameList(string $value): bool
    {
        foreach (explode(',', $value) as $host) {
            $host = trim($host);
            if ($host === '') {
                continue;
            }

            if (preg_match(self::HOSTNAME_PATTERN, $host) !== 1) {
                return false;
            }
        }

        return true;
    }

    /**
     * True when the host is an IP address or resolves to IP addresses that
     * are all outside the loopback, private and link-local ranges.
     */
    public function isPublicHost(string $host, bool $allowPrivateNetworks = false): bool
    {
        $host = trim($host, characters: '[]');
        $isBlocked = $allowPrivateNetworks ? self::isLinkLocalOrReservedIp(...) : self::isInternalIp(...);

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return !$isBlocked($host);
        }

        if (
            !$allowPrivateNetworks
            && (strtolower($host) === 'localhost' || str_ends_with(strtolower($host), '.localhost'))
        ) {
            return false;
        }

        $addresses = ($this->resolver)($host);
        if ($addresses === []) {
            return false;
        }

        return !array_any($addresses, $isBlocked);
    }

    private function httpUrlHost(string $url): ?string
    {
        $url = trim($url);
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower($parts['scheme'] ?? '');
        $host = $parts['host'] ?? '';
        if ($host === '' || !in_array($scheme, self::HTTP_SCHEMES, strict: true)) {
            return null;
        }

        // Credentials in URLs are a classic way to confuse URL parsers
        if (($parts['user'] ?? null) !== null || ($parts['pass'] ?? null) !== null) {
            return null;
        }

        return $host;
    }

    /**
     * Link-local (cloud metadata), unspecified and multicast ranges: never a
     * legitimate service address, even on a private network.
     */
    private static function isLinkLocalOrReservedIp(string $ip): bool
    {
        return IpUtils::checkIp($ip, ['169.254.0.0/16', '0.0.0.0/8', '224.0.0.0/4', 'fe80::/10', '::/128', 'ff00::/8']);
    }

    private static function isInternalIp(string $ip): bool
    {
        return (
            IpUtils::isPrivateIp($ip)
            || IpUtils::checkIp($ip, ['0.0.0.0/8', '100.64.0.0/10', '::/128', '::ffff:0:0/96', '64:ff9b::/96'])
        );
    }

    /**
     * @return list<string>
     */
    private static function resolveWithDns(string $host): array
    {
        $ipv4Addresses = gethostbynamel($host);
        $addresses = $ipv4Addresses === false ? [] : array_values($ipv4Addresses);

        // dns_get_record() emits a warning for unresolvable names instead of returning false
        set_error_handler(static fn(): bool => true);

        try {
            $records = dns_get_record($host, DNS_AAAA);
        } finally {
            restore_error_handler();
        }

        /* @mago-expect analysis:mixed-assignment - dns_get_record() results are untyped; validated below */
        foreach ($records === false ? [] : $records as $record) {
            /* @mago-expect analysis:mixed-assignment - dns_get_record() results are untyped; validated below */
            $ipv6 = $record['ipv6'] ?? null;
            if (!is_string($ipv6)) {
                continue;
            }

            $addresses[] = $ipv6;
        }

        return $addresses;
    }
}
