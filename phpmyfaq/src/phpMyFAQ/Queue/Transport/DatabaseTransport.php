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
use phpMyFAQ\Core\Json;
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

        $availableAt ??= new DateTimeImmutable();
        $availableAtValue = $db->escape($availableAt->format('Y-m-d H:i:s'));
        $queueValue = $db->escape($queue);
        $bodyValue = $db->escape($body);
        $headersValue = $db->escape(json_encode($headers, JSON_THROW_ON_ERROR));

        $query = sprintf(
            "INSERT INTO %s (queue, body, headers, available_at, delivered_at, created) VALUES ('%s', '%s', '%s', '%s', NULL, %s)",
            $table,
            $queueValue,
            $bodyValue,
            $headersValue,
            $availableAtValue,
            $db->now(),
        );

        if ($db->query($query) === false) {
            throw new RuntimeException('Unable to enqueue job: ' . $db->error());
        }

        return (int) $db->lastInsertId();
    }

    /**
     * Reserves the next due job, from the given queue or from any queue if none is given.
     *
     * @return array{id: int, queue: string, body: string, headers: array<string, mixed>}|null
     */
    public function reserve(?string $queue = null): ?array
    {
        $db = $this->configuration->getDb();
        $table = Database::getTablePrefix() . 'faqjobs';
        $queueFilter = $queue === null ? '' : sprintf("queue = '%s' AND ", $db->escape($queue));

        $nowValue = $db->escape(new DateTimeImmutable()->format('Y-m-d H:i:s'));

        $query = sprintf(
            "SELECT id, queue, body, headers FROM %s WHERE %sdelivered_at IS NULL AND available_at <= '%s' ORDER BY available_at ASC, id ASC",
            $table,
            $queueFilter,
            $nowValue,
        );

        $result = $db->query($query, 0, 10);
        if ($result === false) {
            throw new RuntimeException('Unable to fetch queued jobs: ' . $db->error());
        }

        while (true) {
            // The drivers disagree on the end of a result: null, false or an empty array.
            $row = $db->fetchArray($result);
            if (!is_array($row) || $row === []) {
                break;
            }

            $jobId = (int) $row['id'];
            // The reservation time comes from the same clock as available_at and as the cutoff in
            // findStaleReservations(); the database clock may run in another time zone.
            $markDelivered = sprintf(
                "UPDATE %s SET delivered_at = '%s' WHERE id = %d AND delivered_at IS NULL",
                $table,
                $nowValue,
                $jobId,
            );

            if ($db->query($markDelivered) === false) {
                continue;
            }

            if ($db->affectedRows() !== 1) {
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

    /**
     * @param array<string, mixed>|null $headers Updated headers to persist, or null to leave unchanged.
     */
    public function release(int $jobId, ?DateTimeImmutable $availableAt = null, ?array $headers = null): bool
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

        if ($headers !== null) {
            $query = sprintf(
                "UPDATE %s SET delivered_at = NULL, available_at = '%s', headers = '%s' WHERE id = %d",
                $table,
                $db->escape($availableAt->format('Y-m-d H:i:s')),
                $db->escape(json_encode($headers, JSON_THROW_ON_ERROR)),
                $jobId,
            );
        }

        return $db->query($query) !== false;
    }

    /**
     * Returns the jobs reserved before the given time that were neither acknowledged nor released,
     * because the worker that reserved them ended while handling them.
     *
     * @return list<array{id: int, queue: string, body: string, headers: array<string, mixed>}>
     */
    public function findStaleReservations(DateTimeImmutable $reservedBefore): array
    {
        $db = $this->configuration->getDb();

        $query = sprintf(
            "SELECT id, queue, body, headers FROM %s WHERE delivered_at IS NOT NULL AND delivered_at < '%s' ORDER BY id ASC",
            Database::getTablePrefix() . 'faqjobs',
            $db->escape($reservedBefore->format('Y-m-d H:i:s')),
        );

        $result = $db->query($query);
        if ($result === false) {
            throw new RuntimeException('Unable to fetch stale job reservations: ' . $db->error());
        }

        $jobs = [];
        while (true) {
            // The drivers disagree on the end of a result: null, false or an empty array.
            $row = $db->fetchArray($result);
            if (!is_array($row) || $row === []) {
                break;
            }

            $jobs[] = [
                'id' => (int) $row['id'],
                'queue' => (string) $row['queue'],
                'body' => (string) $row['body'],
                'headers' => $this->decodeHeaders((string) ($row['headers'] ?? '')),
            ];
        }

        return $jobs;
    }

    /**
     * Makes a stale reservation available again. The update only applies while the reservation is
     * still older than the given time, so a job another worker has reserved again in the meantime
     * is left alone.
     *
     * @param array<string, mixed> $headers
     */
    public function releaseStaleReservation(int $jobId, DateTimeImmutable $reservedBefore, array $headers): bool
    {
        $db = $this->configuration->getDb();

        $query = sprintf(
            "UPDATE %s SET delivered_at = NULL, available_at = '%s', headers = '%s' WHERE id = %d AND delivered_at < '%s'",
            Database::getTablePrefix() . 'faqjobs',
            $db->escape(new DateTimeImmutable()->format('Y-m-d H:i:s')),
            $db->escape(json_encode($headers, JSON_THROW_ON_ERROR)),
            $jobId,
            $db->escape($reservedBefore->format('Y-m-d H:i:s')),
        );

        return $db->query($query) !== false && $db->affectedRows() === 1;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeHeaders(string $rawHeaders): array
    {
        if ($rawHeaders === '') {
            return [];
        }

        $decoded = Json::decodeAssoc($rawHeaders);
        if ($decoded === null) {
            return [];
        }

        $headers = [];
        foreach ($decoded as $headerName => $headerValue) {
            $headers[(string) $headerName] = $headerValue;
        }

        return $headers;
    }
}
