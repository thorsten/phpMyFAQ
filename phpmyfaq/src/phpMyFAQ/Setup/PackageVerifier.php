<?php

/**
 * Verifies downloaded phpMyFAQ packages against their published checksums.
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

namespace phpMyFAQ\Setup;

use JsonException;
use Monolog\Level;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\DownloadHostType;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Release packages are checked against download.phpmyfaq.de/info/{version}
 * (SHA-256 when published, MD5 for older releases). Nightly builds are
 * checked against the SHA-256 digest GitHub publishes for the release asset.
 * Detached signature verification needs a signing step in the release
 * process first and is a follow-up.
 */
final readonly class PackageVerifier
{
    final public const string GITHUB_RELEASE_API = 'https://api.github.com/repos/thorsten/phpMyFAQ/releases/tags/development-nightly-%s';

    public function __construct(
        private Configuration $configuration,
        private HttpClientInterface $httpClient,
    ) {
    }

    /**
     * Unreachable release information or an unparsable response counts as a
     * failed verification; the package is never installed on a hunch.
     *
     * @throws TransportExceptionInterface
     */
    public function verifyRelease(string $path, string $version): bool
    {
        $response = $this->httpClient->request(
            method: 'GET',
            url: DownloadHostType::PHPMYFAQ->value . 'info/' . $version,
        );

        try {
            /* @mago-expect analysis:mixed-assignment - the release information is untyped JSON; validated below */
            $responseContent = json_decode(
                $response->getContent(),
                associative: true,
                depth: 512,
                flags: JSON_THROW_ON_ERROR,
            );

            $zipInfo = is_array($responseContent) && is_array($responseContent['zip'] ?? null)
                ? $responseContent['zip']
                : [];

            return $this->matchesPublishedChecksums($path, $zipInfo);
        } catch (
            TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException $e
        ) {
            $this->configuration->getLogger()->log(Level::Error, $e->getMessage());

            return false;
        }
    }

    /**
     * A nightly whose digest is not available is refused unless
     * upgrade.allowUnverifiedNightly is explicitly enabled.
     */
    public function verifyNightly(string $path): bool
    {
        $assetName = basename($path);
        $digest = $this->fetchNightlyDigest($assetName);

        if ($digest !== null) {
            return hash_equals($digest, (string) hash_file('sha256', $path));
        }

        if (!$this->isUnverifiedNightlyAllowed()) {
            return false;
        }

        $this->configuration->getLogger()->log(Level::Warning, sprintf(
            'Installing nightly package %s without digest verification.',
            $assetName,
        ));

        return true;
    }

    public function isUnverifiedNightlyAllowed(): bool
    {
        return filter_var($this->configuration->get(item: 'upgrade.allowUnverifiedNightly'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * SHA-256 is authoritative when the release information provides it; the
     * legacy MD5 sum is only accepted on its own for releases that predate
     * the SHA-256 field. Any published checksum that does not match fails.
     *
     * @param array<array-key, mixed> $zipInfo
     */
    private function matchesPublishedChecksums(string $path, array $zipInfo): bool
    {
        /* @mago-expect analysis:mixed-assignment - the release information is untyped JSON; validated below */
        $expectedSha256 = $zipInfo['sha256'] ?? null;
        /* @mago-expect analysis:mixed-assignment - the release information is untyped JSON; validated below */
        $expectedMd5 = $zipInfo['md5'] ?? null;

        if (!is_string($expectedSha256) && !is_string($expectedMd5)) {
            return false;
        }

        if (
            is_string($expectedSha256) && !hash_equals(strtolower($expectedSha256), (string) hash_file('sha256', $path))
        ) {
            return false;
        }

        return !is_string($expectedMd5) || hash_equals(strtolower($expectedMd5), (string) md5_file($path));
    }

    private function fetchNightlyDigest(string $assetName): ?string
    {
        try {
            $response = $this->httpClient->request(
                method: 'GET',
                url: sprintf(self::GITHUB_RELEASE_API, date(format: 'Y-m-d')),
                options: ['headers' => ['Accept' => 'application/vnd.github+json']],
            );

            /* @mago-expect analysis:mixed-assignment - the GitHub release information is untyped JSON; validated below */
            $release = json_decode($response->getContent(), associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

            return $this->findAssetDigest(is_array($release) ? $release : [], $assetName);
        } catch (
            TransportExceptionInterface|ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|JsonException $exception
        ) {
            $this->configuration->getLogger()->log(Level::Error, $exception->getMessage());

            return null;
        }
    }

    /**
     * Returns the lowercase hex SHA-256 from the "digest" field ("sha256:<hex>")
     * of the named release asset, or null when GitHub does not provide one.
     *
     * @param array<array-key, mixed> $release
     */
    private function findAssetDigest(array $release, string $assetName): ?string
    {
        $assets = is_array($release['assets'] ?? null) ? $release['assets'] : [];

        /* @mago-expect analysis:mixed-assignment - the GitHub release information is untyped JSON; validated below */
        foreach ($assets as $asset) {
            if (!is_array($asset) || ($asset['name'] ?? null) !== $assetName) {
                continue;
            }

            /* @mago-expect analysis:mixed-assignment - the GitHub release information is untyped JSON; validated below */
            $digest = $asset['digest'] ?? null;
            $matches = [];
            if (!is_string($digest) || preg_match('/^sha256:([0-9a-f]{64})$/i', $digest, $matches) !== 1) {
                return null;
            }

            return strtolower($matches[1]);
        }

        return null;
    }
}
