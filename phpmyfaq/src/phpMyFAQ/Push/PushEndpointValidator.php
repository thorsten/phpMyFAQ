<?php

/**
 * Validates Web Push subscription endpoints before they are stored.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Push
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-09-13
 */

declare(strict_types=1);

namespace phpMyFAQ\Push;

use Closure;

/**
 * A stored endpoint is later POSTed to by the server, so an attacker-supplied endpoint
 * is a blind SSRF primitive. Only public HTTPS hosts are accepted: every address the host
 * resolves to must lie outside loopback, private (RFC 1918), CGNAT, link-local, unique
 * local, site-local, multicast, reserved and documentation ranges.
 */
final readonly class PushEndpointValidator
{
    /**
     * Address blocks that are never a legitimate push service.
     */
    private const array BLOCKED_RANGES = [
        '0.0.0.0/8',
        '10.0.0.0/8',
        '100.64.0.0/10',
        '127.0.0.0/8',
        '169.254.0.0/16',
        '172.16.0.0/12',
        '192.0.0.0/24',
        '192.0.2.0/24',
        '192.168.0.0/16',
        '198.18.0.0/15',
        '198.51.100.0/24',
        '203.0.113.0/24',
        '224.0.0.0/4',
        '240.0.0.0/4',
        '::/128',
        '::1/128',
        '::ffff:0:0/96',
        '64:ff9b::/96',
        '100::/64',
        '2001:db8::/32',
        'fc00::/7',
        'fe80::/10',
        'fec0::/10',
        'ff00::/8',
    ];

    private const int BITS_PER_BYTE = 8;

    /**
     * @var Closure(string): array<string>
     */
    private Closure $resolver;

    /**
     * @param (Closure(string): array<string>)|null $resolver resolves a host name to its IPv4
     *                                                        and IPv6 addresses; defaults to a
     *                                                        DNS lookup
     */
    public function __construct(?Closure $resolver = null)
    {
        $this->resolver = $resolver ?? self::resolveWithDns(...);
    }

    public function isValid(string $endpoint): bool
    {
        if (filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($endpoint);
        if (!is_array($parts)) {
            return false;
        }

        if (strtolower($parts['scheme'] ?? '') !== 'https') {
            return false;
        }

        if (array_key_exists('user', $parts) || array_key_exists('pass', $parts)) {
            return false;
        }

        $host = strtolower(trim($parts['host'] ?? '', characters: '[]'));
        if ($host === '') {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isPublicAddress($host);
        }

        if (filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        if ($host === 'localhost' || str_ends_with($host, '.localhost') || str_ends_with($host, '.local')) {
            return false;
        }

        $addresses = ($this->resolver)($host);
        if ($addresses === []) {
            return false;
        }

        return array_all($addresses, $this->isPublicAddress(...));
    }

    private function isPublicAddress(string $address): bool
    {
        $flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        if (filter_var($address, FILTER_VALIDATE_IP, $flags) === false) {
            return false;
        }

        $packed = inet_pton($address);
        if ($packed === false) {
            return false;
        }

        // The filter flags do not cover multicast, CGNAT, NAT64 or documentation ranges.
        return !array_any(self::BLOCKED_RANGES, static fn(string $range): bool => self::inRange($packed, $range));
    }

    private static function inRange(string $packed, string $range): bool
    {
        [$network, $prefixLength] = explode('/', $range);
        $packedNetwork = inet_pton($network);
        if ($packedNetwork === false || strlen($packedNetwork) !== strlen($packed)) {
            return false;
        }

        $prefixLength = (int) $prefixLength;
        $fullBytes = intdiv($prefixLength, self::BITS_PER_BYTE);
        if (substr($packed, offset: 0, length: $fullBytes) !== substr($packedNetwork, offset: 0, length: $fullBytes)) {
            return false;
        }

        $remainingBits = $prefixLength % self::BITS_PER_BYTE;
        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xff << (self::BITS_PER_BYTE - $remainingBits)) & 0xff;

        return (ord($packed[$fullBytes]) & $mask) === (ord($packedNetwork[$fullBytes]) & $mask);
    }

    /**
     * @return array<string>
     */
    private static function resolveWithDns(string $host): array
    {
        $addresses = gethostbynamel($host);
        $addresses = $addresses === false ? [] : $addresses;

        try {
            $records = dns_get_record($host, DNS_AAAA);
        } catch (\Throwable) {
            $records = false;
        }

        /* @mago-expect analysis:mixed-assignment - dns_get_record() results are untyped */
        foreach ($records === false ? [] : $records as $record) {
            if (!is_array($record) || !is_string($record['ipv6'] ?? null)) {
                continue;
            }

            $addresses[] = $record['ipv6'];
        }

        return array_values(array_unique($addresses));
    }
}
