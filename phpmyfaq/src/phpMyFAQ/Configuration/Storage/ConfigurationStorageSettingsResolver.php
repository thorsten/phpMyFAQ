<?php

/**
 * Resolves configuration storage settings from database values.
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
 * @since     2026-02-23
 */

declare(strict_types=1);

namespace phpMyFAQ\Configuration\Storage;

readonly class ConfigurationStorageSettingsResolver
{
    private const string DEFAULT_REDIS_DSN = 'tcp://redis:6379?database=1';
    private const string DEFAULT_REDIS_PREFIX = 'pmf:config:';
    private const float DEFAULT_CONNECT_TIMEOUT = 1.0;

    public function __construct(
        private DatabaseConfigurationStore $databaseConfigurationStore,
    ) {
    }

    public function resolve(): ConfigurationStorageSettings
    {
        $enabledValue = strtolower(
            (string) ($this->databaseConfigurationStore->fetchValue('storage.useRedisForConfiguration') ?? 'false'),
        );
        $enabled = in_array($enabledValue, ['1', 'true', 'yes', 'on'], true);

        $redisDsn = trim((string) ($this->databaseConfigurationStore->fetchValue('storage.redisDsn') ?? ''));
        if ($redisDsn === '') {
            $redisDsn = self::DEFAULT_REDIS_DSN;
        }

        $redisPrefix = (string) ($this->databaseConfigurationStore->fetchValue('storage.redisPrefix') ?? '');
        if ($redisPrefix === '') {
            $redisPrefix = self::DEFAULT_REDIS_PREFIX;
        }

        $connectTimeout = (float) ($this->databaseConfigurationStore->fetchValue('storage.redisConnectTimeout') ?? '');
        if ($connectTimeout <= 0) {
            $connectTimeout = self::DEFAULT_CONNECT_TIMEOUT;
        }

        return new ConfigurationStorageSettings($enabled, $redisDsn, $redisPrefix, $connectTimeout);
    }
}
