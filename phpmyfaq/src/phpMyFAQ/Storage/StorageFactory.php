<?php

/**
 * Storage factory.
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
 * @since     2026-02-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Storage;

use Aws\S3\S3Client;
use phpMyFAQ\Configuration;

final readonly class StorageFactory
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    public function create(): StorageInterface
    {
        $type = strtolower((string) ($this->configuration->get('storage.type') ?? 'filesystem'));

        return match ($type) {
            'filesystem' => new FilesystemStorage(
                $this->resolveFilesystemRoot(),
                $this->readStringConfig('storage.filesystem.publicBaseUrl'),
            ),
            's3' => $this->createS3Storage(),
            default => throw new StorageException('Unsupported storage type: ' . $type),
        };
    }

    private function createS3Storage(): S3Storage
    {
        $bucket = $this->readRequiredConfig('storage.s3.bucket');
        $prefix = $this->readStringConfig('storage.s3.prefix') ?? '';
        $publicBaseUrl = $this->readStringConfig('storage.s3.publicBaseUrl');
        $region = $this->readStringConfig('storage.s3.region') ?? 'us-east-1';

        if (!class_exists(S3Client::class)) {
            throw new StorageException('AWS SDK for PHP is required for S3 storage.');
        }

        $s3Config = [
            'version' => 'latest',
            'region' => $region,
        ];

        $endpoint = $this->readStringConfig('storage.s3.endpoint');
        if ($endpoint !== null && $endpoint !== '') {
            $s3Config['endpoint'] = $endpoint;
        }

        $key = $this->readStringConfig('storage.s3.key');
        $secret = $this->readStringConfig('storage.s3.secret');
        if (($key === null) !== ($secret === null)) {
            throw new StorageException('Both storage.s3.key and storage.s3.secret must be provided together.');
        }

        if ($key !== null && $secret !== null) {
            $s3Config['credentials'] = [
                'key' => $key,
                'secret' => $secret,
            ];
        }

        $usePathStyle = $this->configuration->get('storage.s3.usePathStyle');
        if ($usePathStyle !== null) {
            $s3Config['use_path_style_endpoint'] = filter_var($usePathStyle, FILTER_VALIDATE_BOOL);
        }

        /** @var object $client */
        $client = new S3Client($s3Config);

        return new S3Storage($client, $bucket, $prefix, $publicBaseUrl);
    }

    private function resolveFilesystemRoot(): string
    {
        $configuredRoot = $this->readStringConfig('storage.filesystem.root');
        if ($configuredRoot !== null && $configuredRoot !== '') {
            return $configuredRoot;
        }

        if (defined('PMF_ATTACHMENTS_DIR') && PMF_ATTACHMENTS_DIR !== false) {
            return (string) PMF_ATTACHMENTS_DIR;
        }

        return PMF_ROOT_DIR . '/content/user/attachments';
    }

    private function readRequiredConfig(string $key): string
    {
        $value = $this->readStringConfig($key);
        if ($value === null || $value === '') {
            throw new StorageException('Missing required storage configuration key: ' . $key);
        }

        return $value;
    }

    private function readStringConfig(string $key): ?string
    {
        $value = $this->configuration->get($key);
        if ($value === null) {
            return null;
        }

        return trim((string) $value);
    }
}
