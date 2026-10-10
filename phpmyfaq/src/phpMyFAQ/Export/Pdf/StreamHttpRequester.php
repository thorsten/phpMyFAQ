<?php

/**
 * Default HttpRequesterInterface implementation, backed by PHP's HTTP stream wrapper.
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
 * @since     2026-08-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Export\Pdf;

use Override;

/**
 * Class StreamHttpRequester
 *
 * Redirects are intentionally disabled (`follow_location: false`) and TLS
 * verification is enabled — the caller (ExternalImageFetcher) is responsible
 * for revalidating the host allowlist on every redirect hop.
 *
 * Before opening the connection the host is resolved and every address it maps to
 * must be a routable unicast address: loopback, link-local (cloud metadata
 * endpoints), unspecified, multicast and other reserved ranges are refused. The
 * request is then pinned to the vetted literal address (keeping the original host
 * in the Host header and for TLS verification), so a name that resolves to an
 * internal destination, or a DNS answer that changes between the check and the
 * connect, cannot turn the fetch into a server-side request against the host's own
 * network (SSRF, CWE-918).
 *
 * @package phpMyFAQ\Export\Pdf
 */
final class StreamHttpRequester implements HttpRequesterInterface
{
    #[Override]
    public function request(string $url): array
    {
        $pinned = $this->resolvePinnedTarget($url);
        if ($pinned === null) {
            return [0, [], false];
        }

        [$connectUrl, $hostHeader, $peerName] = $pinned;

        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10-second timeout
                'user_agent' => 'phpMyFAQ PDF Generator/1.0',
                'follow_location' => false,
                'ignore_errors' => true,
                // Keep the original host name on the wire; the connection itself goes to the
                // vetted literal address in $connectUrl.
                'header' => 'Host: ' . $hostHeader,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                // Verify the certificate against the real host name, not the literal address.
                'peer_name' => $peerName,
            ],
        ]);

        $body = file_get_contents($connectUrl, use_include_path: false, context: $context);
        $responseHeaders = $http_response_header ?? [];

        return [$this->parseHttpStatusCode($responseHeaders), $responseHeaders, $body];
    }

    /**
     * Validates the URL's scheme, resolves its host to a routable address and returns the literal
     * connect URL together with the Host header and TLS peer name to use, or null when the host
     * must not be contacted.
     *
     * @return array{0: string, 1: string, 2: string}|null [connectUrl, hostHeader, peerName]
     */
    private function resolvePinnedTarget(string $url): ?array
    {
        $parsedUrl = parse_url($url);
        if ($parsedUrl === false || !array_key_exists('scheme', $parsedUrl) || !array_key_exists('host', $parsedUrl)) {
            return null;
        }

        $scheme = strtolower($parsedUrl['scheme']);
        if ($scheme !== 'http' && $scheme !== 'https') {
            return null;
        }

        $host = MediaHostPolicy::normalizeHost($parsedUrl['host']);
        $address = $this->resolveSafeAddress($host);
        if ($address === null) {
            return null;
        }

        $port = array_key_exists('port', $parsedUrl) ? (int) $parsedUrl['port'] : null;
        $hostHeader = $parsedUrl['host'] . ($port !== null ? ':' . $port : '');
        $connectHost = str_contains($address, ':') ? '[' . $address . ']' : $address;
        $connectUrl =
            $scheme
            . '://'
            . $connectHost
            . ($port !== null ? ':' . $port : '')
            . ($parsedUrl['path'] ?? '/')
            . (array_key_exists('query', $parsedUrl) ? '?' . $parsedUrl['query'] : '');

        return [$connectUrl, $hostHeader, $host];
    }

    /**
     * Resolves a host name to the IP address the fetcher may connect to.
     *
     * Every address the name resolves to must be a routable unicast address: loopback, link-local
     * (cloud metadata endpoints), unspecified, multicast and other reserved ranges are refused.
     * RFC 1918 / ULA private ranges stay allowed because intranet image hosts are a legitimate
     * configuration; the allowlist and port policy in ExternalImageFetcher limit their exposure.
     *
     * @return string|null The vetted IP address, or null if the host must not be contacted
     */
    private function resolveSafeAddress(string $host): ?string
    {
        $host = MediaHostPolicy::normalizeHost($host);
        if ($host === '') {
            return null;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return $this->isRoutableAddress($host) ? $host : null;
        }

        $addresses = [];
        $ipv4 = gethostbynamel($host);
        if (is_array($ipv4)) {
            $addresses = $ipv4;
        }

        // dns_get_record() warns on resolver failures; phpMyFAQ turns warnings into exceptions
        // globally, and an unresolvable host is simply skipped.
        set_error_handler(static fn(): bool => true, E_WARNING);
        try {
            $records = dns_get_record($host, DNS_AAAA);
        } finally {
            restore_error_handler();
        }

        if (is_array($records)) {
            /** @var array<array-key, mixed> $record */
            foreach ($records as $record) {
                if (!is_string($record['ipv6'] ?? null)) {
                    continue;
                }

                $addresses[] = $record['ipv6'];
            }
        }

        $addresses = array_values(array_unique($addresses));
        if ($addresses === []) {
            return null;
        }

        foreach ($addresses as $address) {
            if (!$this->isRoutableAddress($address)) {
                return null;
            }
        }

        return $addresses[0];
    }

    /**
     * Tells whether an IP address is a routable unicast address outside the loopback, link-local,
     * unspecified, multicast and reserved ranges.
     */
    private function isRoutableAddress(string $address): bool
    {
        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        if (filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $firstOctet = (int) explode('.', $address)[0];

            // 224.0.0.0/4 multicast, 255.255.255.255 broadcast
            return $firstOctet < 224;
        }

        $packed = inet_pton($address);
        if ($packed === false || strlen($packed) !== 16) {
            return false;
        }

        // ff00::/8 multicast
        if (ord($packed[0]) === 0xff) {
            return false;
        }

        // IPv4-mapped (::ffff:a.b.c.d) and IPv4-compatible (::a.b.c.d) addresses must satisfy the
        // IPv4 policy for the embedded address.
        $prefix = substr($packed, offset: 0, length: 12);
        if ($prefix === "\0\0\0\0\0\0\0\0\0\0\xff\xff" || $prefix === "\0\0\0\0\0\0\0\0\0\0\0\0") {
            $embedded = inet_ntop(substr($packed, offset: 12, length: 4));

            return $embedded !== false && $this->isRoutableAddress($embedded);
        }

        return true;
    }

    /**
     * @param string[] $responseHeaders
     */
    private function parseHttpStatusCode(array $responseHeaders): int
    {
        $statusLine = $responseHeaders[0] ?? null;
        if (!is_string($statusLine)) {
            return 0;
        }

        $matches = [];
        if (!preg_match('/^HTTP\/\S+\s+(\d{3})/', $statusLine, $matches)) {
            return 0;
        }

        return (int) $matches[1];
    }
}
