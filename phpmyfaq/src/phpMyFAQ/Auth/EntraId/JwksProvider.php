<?php

/**
 * JWKS provider for Entra ID (Microsoft Identity Platform).
 *
 * Fetches and caches the JSON Web Key Set used to verify id_token signatures.
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
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\EntraId;

use Firebase\JWT\JWK;
use Firebase\JWT\Key;
use RuntimeException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class JwksProvider
{
    private const int CACHE_TTL_SECONDS = 86_400;

    private HttpClientInterface $httpClient;

    private string $cacheDir;

    public function __construct(?HttpClientInterface $httpClient = null, ?string $cacheDir = null)
    {
        $this->httpClient = $httpClient ?? HttpClient::create();
        $this->cacheDir =
            $cacheDir
            ?? (defined('PMF_ROOT_DIR') ? PMF_ROOT_DIR . '/cache/jwks' : sys_get_temp_dir() . '/phpmyfaq-jwks');
    }

    /**
     * @return array<string, Key>
     */
    public function getKeys(string $tenantId): array
    {
        $jwks = $this->loadJwks($tenantId);

        return JWK::parseKeySet($jwks);
    }

    /**
     * @return array{keys: array<int, array<string, mixed>>}
     */
    private function loadJwks(string $tenantId): array
    {
        $cacheFile = $this->cacheFile($tenantId);

        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < self::CACHE_TTL_SECONDS) {
            $cached = file_get_contents($cacheFile);
            if ($cached !== false) {
                $decoded = json_decode($cached, associative: true);
                if (is_array($decoded) && array_key_exists('keys', $decoded) && is_array($decoded['keys'])) {
                    return $decoded;
                }
            }
        }

        $url = 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/discovery/v2.0/keys';
        $response = $this->httpClient->request('GET', $url);
        if ($response->getStatusCode() !== 200) {
            throw new RuntimeException(sprintf(
                'Failed to fetch JWKS from %s (HTTP %d)',
                $url,
                $response->getStatusCode(),
            ));
        }

        $body = $response->getContent();
        $decoded = json_decode($body, associative: true);
        if (!is_array($decoded) || !array_key_exists('keys', $decoded) || !is_array($decoded['keys'])) {
            throw new RuntimeException('Malformed JWKS response from identity provider.');
        }

        $this->writeCache($cacheFile, $body);

        return $decoded;
    }

    private function cacheFile(string $tenantId): string
    {
        return rtrim($this->cacheDir, characters: '/') . '/jwks-' . sha1($tenantId) . '.json';
    }

    private function writeCache(string $file, string $body): void
    {
        $dir = dirname($file);
        if (!is_dir($dir)) {
            set_error_handler(static fn(): bool => true);
            try {
                mkdir(directory: $dir, permissions: 0o775, recursive: true);
            } finally {
                restore_error_handler();
            }
            if (!is_dir($dir)) {
                return;
            }
        }
        set_error_handler(static fn(): bool => true);
        try {
            file_put_contents(filename: $file, data: $body, flags: LOCK_EX);
        } finally {
            restore_error_handler();
        }
    }
}
