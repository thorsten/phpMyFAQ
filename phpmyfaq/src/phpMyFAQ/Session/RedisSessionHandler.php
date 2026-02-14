<?php

/**
 * Configures native PHP Redis-backed sessions with connectivity checks.
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
 * @since     2026-02-14
 */

declare(strict_types=1);

namespace phpMyFAQ\Session;

use RuntimeException;

class RedisSessionHandler
{
    public const string DEFAULT_DSN = 'tcp://redis:6379?database=0';

    public static function configure(string $dsn = ''): void
    {
        if (!extension_loaded('redis')) {
            throw new RuntimeException('Redis session handler requires the PHP redis extension (ext-redis).');
        }

        $redisDsn = trim($dsn) !== '' ? trim($dsn) : self::DEFAULT_DSN;
        self::validateConnection($redisDsn);

        ini_set('session.save_handler', value: 'redis');
        ini_set('session.save_path', value: $redisDsn);
    }

    public static function validateConnection(string $dsn, float $timeoutSeconds = 1.0): void
    {
        [$socketTarget, $displayTarget] = self::buildSocketTarget($dsn);

        $errno = 0;
        $errorString = '';
        $connection = @stream_socket_client(
            $socketTarget,
            $errno,
            $errorString,
            $timeoutSeconds,
            STREAM_CLIENT_CONNECT,
        );

        if ($connection === false) {
            throw new RuntimeException(sprintf(
                'Redis session handler is configured but unreachable (%s): %s',
                $displayTarget,
                $errorString !== '' ? $errorString : 'connection failed',
            ));
        }

        fclose($connection);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private static function buildSocketTarget(string $dsn): array
    {
        $parsedUrl = parse_url($dsn);
        if ($parsedUrl === false || !isset($parsedUrl['scheme'])) {
            throw new RuntimeException('Invalid Redis DSN for sessions.');
        }

        $scheme = strtolower((string) $parsedUrl['scheme']);
        if ($scheme === 'redis' || $scheme === 'tcp') {
            $host = $parsedUrl['host'] ?? '127.0.0.1';
            $port = (int) ($parsedUrl['port'] ?? 6379);
            return [sprintf('tcp://%s:%d', $host, $port), sprintf('%s:%d', $host, $port)];
        }

        if ($scheme === 'unix') {
            $path = $parsedUrl['path'] ?? '';
            if ($path === '') {
                throw new RuntimeException('Invalid Redis unix socket DSN for sessions.');
            }

            return ['unix://' . $path, $path];
        }

        throw new RuntimeException(sprintf('Unsupported Redis DSN scheme "%s" for sessions.', $scheme));
    }
}
