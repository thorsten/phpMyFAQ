<?php

/**
 * Database-backed queue transport.
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
 * @since     2026-02-11
 */

declare(strict_types=1);

namespace phpMyFAQ\Queue\Transport;

use DateTimeImmutable;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use RuntimeException;

readonly class DatabaseTransport
{
    public function __construct(
        private Configuration $configuration,
    ) {
    }

    /**
     * @param array<string, mixed> $headers
     */
    public function enqueue(
        string $body,
        array $headers = [],
        string $queue = 'default',
        ?DateTimeImmutable $availableAt = null,
    ): int {
        $db = $this->configuration->getDb();
        $table = Database::getTablePrefix() . 'faqjobs';
        $id = $db->nextId($table, 'id');

        $availableAt ??= new DateTimeImmutable();
        $availableAtValue = $db->escape($availableAt->format('Y-m-d H:i:s'));
        $queueValue = $db->escape($queue);
        $bodyValue = $db->escape($body);
        $headersValue = $db->escape((string) json_encode($headers, JSON_THROW_ON_ERROR));

        $query = sprintf(
            "INSERT INTO %s (id, queue, body, headers, available_at, delivered_at, created) VALUES (%d, '%s', '%s', '%s', '%s', NULL, %s)",
            $table,
            $id,
            $queueValue,
            $bodyValue,
            $headersValue,
            $availableAtValue,
            $db->now(),
        );

        if ($db->query($query) === false) {
            throw new RuntimeException('Unable to enqueue job: ' . $db->error());
        }

        return $id;
    }

    /**
     * @return array{id: int, queue: string, body: string, headers: array<string, mixed>}|null
     */
    public function reserve(string $queue = 'default'): ?array
    {
        $db = $this->configuration->getDb();
        $table = Database::getTablePrefix() . 'faqjobs';
        $escapedQueue = $db->escape($queue);

        $query = sprintf(
            "SELECT id, queue, body, headers, available_at FROM %s WHERE queue = '%s' AND delivered_at IS NULL ORDER BY available_at ASC, id ASC",
            $table,
            $escapedQueue,
        );

        $result = $db->query($query);
        if ($result === false) {
            throw new RuntimeException('Unable to fetch queued jobs: ' . $db->error());
        }

        $now = time();
        while ($row = $db->fetchArray($result)) {
            $availableAt = strtotime((string) ($row['available_at'] ?? ''));
            if ($availableAt !== false && $availableAt > $now) {
                continue;
            }

            $jobId = (int) $row['id'];
            $markDelivered = sprintf(
                'UPDATE %s SET delivered_at = %s WHERE id = %d AND delivered_at IS NULL',
                $table,
                $db->now(),
                $jobId,
            );

            if ($db->query($markDelivered) === false) {
                continue;
            }

            return [
                'id' => $jobId,
                'queue' => (string) $row['queue'],
                'body' => (string) $row['body'],
                'headers' => $this->decodeHeaders((string) ($row['headers'] ?? '')),
            ];
        }

        return null;
    }

    public function acknowledge(int $jobId): bool
    {
        $db = $this->configuration->getDb();
        $table = Database::getTablePrefix() . 'faqjobs';
        $query = sprintf('DELETE FROM %s WHERE id = %d', $table, $jobId);

        return $db->query($query) !== false;
    }

    public function release(int $jobId, ?DateTimeImmutable $availableAt = null): bool
    {
        $db = $this->configuration->getDb();
        $table = Database::getTablePrefix() . 'faqjobs';
        $availableAt ??= new DateTimeImmutable('+60 seconds');

        $query = sprintf(
            "UPDATE %s SET delivered_at = NULL, available_at = '%s' WHERE id = %d",
            $table,
            $db->escape($availableAt->format('Y-m-d H:i:s')),
            $jobId,
        );

        return $db->query($query) !== false;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeHeaders(string $rawHeaders): array
    {
        if ($rawHeaders === '') {
            return [];
        }

        $decoded = json_decode($rawHeaders, true);
        return is_array($decoded) ? $decoded : [];
    }
}
