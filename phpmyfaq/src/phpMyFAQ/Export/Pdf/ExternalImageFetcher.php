<?php

/**
 * Fetches external images for PDF export while enforcing a host allowlist.
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

/**
 * Class ExternalImageFetcher
 *
 * Manually follows redirects (instead of delegating to the stream wrapper) so the
 * host allowlist is re-checked on every hop. Without this, an allowed origin could
 * redirect the request to a disallowed destination and bypass the allowlist entirely.
 *
 * @package phpMyFAQ\Export\Pdf
 */
class ExternalImageFetcher
{
    private const int MAX_REDIRECTS = 3;

    private readonly HttpRequesterInterface $httpRequester;

    public function __construct(?HttpRequesterInterface $httpRequester = null)
    {
        $this->httpRequester = $httpRequester ?? new StreamHttpRequester();
    }

    /**
     * Fetches a URL, following up to three redirects and revalidating the host
     * allowlist on every hop.
     *
     * @param string[] $allowedHosts
     * @return string|false The response body, or false if the URL, its scheme, or
     *                       any redirect destination is not allowed, or the request fails.
     */
    public function fetch(string $url, array $allowedHosts): false|string
    {
        $remainingRedirects = self::MAX_REDIRECTS;

        while (true) {
            if (!$this->isFetchableUrl($url, $allowedHosts)) {
                return false;
            }

            [$statusCode, $responseHeaders, $body] = $this->httpRequester->request($url);

            if ($statusCode >= 300 && $statusCode < 400) {
                if ($remainingRedirects <= 0) {
                    return false;
                }

                $location = $this->findResponseHeader($responseHeaders, 'Location');
                $nextUrl = $location !== null ? $this->resolveRedirectLocation($url, $location) : null;
                if ($nextUrl === null) {
                    return false;
                }

                $url = $nextUrl;
                --$remainingRedirects;
                continue;
            }

            if ($body === false || $body === '' || $statusCode < 200 || $statusCode >= 300) {
                return false;
            }

            return $body;
        }
    }

    /**
     * Returns true if the given host matches an allowed host exactly or as a subdomain.
     *
     * @param string[] $allowedHosts
     */
    private function isHostAllowed(string $host, array $allowedHosts): bool
    {
        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = trim($allowedHost);
            if ($allowedHost === '' || $allowedHost === '0') {
                continue;
            }

            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns true if the URL uses an HTTP(S) scheme and its host is allowed.
     *
     * @param string[] $allowedHosts
     */
    private function isFetchableUrl(string $url, array $allowedHosts): bool
    {
        $parsedUrl = parse_url($url);

        return (
            $parsedUrl !== false
            && array_key_exists('scheme', $parsedUrl)
            && in_array($parsedUrl['scheme'], ['http', 'https'], strict: true)
            && array_key_exists('host', $parsedUrl)
            && $this->isHostAllowed($parsedUrl['host'], $allowedHosts)
        );
    }

    /**
     * Resolves a Location header value (absolute, protocol-relative, or path-relative)
     * against the URL it was received for.
     */
    private function resolveRedirectLocation(string $currentUrl, string $location): ?string
    {
        $location = trim($location);
        if ($location === '') {
            return null;
        }

        if (parse_url($location, component: PHP_URL_SCHEME) !== null) {
            return $location;
        }

        $base = parse_url($currentUrl);
        if ($base === false || !array_key_exists('scheme', $base) || !array_key_exists('host', $base)) {
            return null;
        }

        if (str_starts_with($location, '//')) {
            return $base['scheme'] . ':' . $location;
        }

        $origin =
            $base['scheme'] . '://' . $base['host'] . (array_key_exists('port', $base) ? ':' . $base['port'] : '');

        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $currentDir = rtrim(dirname($base['path'] ?? '/'), characters: '/');
        return $origin . $currentDir . '/' . $location;
    }

    /**
     * @param string[] $responseHeaders
     */
    private function findResponseHeader(array $responseHeaders, string $name): ?string
    {
        foreach ($responseHeaders as $header) {
            if (stripos($header, $name . ':') === 0) {
                return trim(substr($header, strlen($name) + 1));
            }
        }

        return null;
    }
}
