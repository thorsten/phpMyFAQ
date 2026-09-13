<?php

/**
 * Elasticsearch's configuration class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2023-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2023-04-30
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

readonly class ElasticsearchConfiguration
{
    /** @var string[] */
    private array $hosts;

    private string $index;

    /**
     * TLS peer verification is on unless the configuration file explicitly
     * sets $PMF_ES['verify_peer'] = false (e.g. for a self-signed test cluster).
     */
    private bool $verifyPeer;

    private ?string $caFile;

    private ?string $caPath;

    public function __construct(string $filename)
    {
        $PMF_ES = [
            'hosts' => [],
            'index' => '',
            'verify_peer' => true,
            'cafile' => null,
            'capath' => null,
        ];

        include $filename;

        $this->hosts = $PMF_ES['hosts'];
        $this->index = $PMF_ES['index'];

        // The configuration file may replace the whole array, so every optional key needs its default again
        /** @var array<string, mixed> $PMF_ES */
        $this->verifyPeer = filter_var($PMF_ES['verify_peer'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $this->caFile = self::optionalPath($PMF_ES['cafile'] ?? null);
        $this->caPath = self::optionalPath($PMF_ES['capath'] ?? null);
    }

    public function isPeerVerificationEnabled(): bool
    {
        return $this->verifyPeer;
    }

    public function getCaFile(): ?string
    {
        return $this->caFile;
    }

    public function getCaPath(): ?string
    {
        return $this->caPath;
    }

    /**
     * Options for Symfony HttpClient based clients (health check, OpenSearch).
     *
     * @return array<string, bool|string>
     */
    public function getTlsClientOptions(): array
    {
        $options = ['verify_peer' => $this->verifyPeer, 'verify_host' => $this->verifyPeer];

        if ($this->caFile !== null) {
            $options['cafile'] = $this->caFile;
        }

        if ($this->caPath !== null) {
            $options['capath'] = $this->caPath;
        }

        return $options;
    }

    private static function optionalPath(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }

    /**
     * @return string[]
     */
    public function getHosts(): array
    {
        return $this->hosts;
    }

    public function getIndex(): string
    {
        return $this->index;
    }
}
