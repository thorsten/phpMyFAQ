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
 * @package phpMyFAQ\Export\Pdf
 */
final class StreamHttpRequester implements HttpRequesterInterface
{
    #[Override]
    public function request(string $url): array
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 10, // 10-second timeout
                'user_agent' => 'phpMyFAQ PDF Generator/1.0',
                'follow_location' => false,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $body = file_get_contents($url, use_include_path: false, context: $context);
        $responseHeaders = $http_response_header ?? [];

        return [$this->parseHttpStatusCode($responseHeaders), $responseHeaders, $body];
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
