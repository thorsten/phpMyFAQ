<?php

/**
 * Queue worker.
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

namespace phpMyFAQ\Queue;

use DateTimeImmutable;
use InvalidArgumentException;
use phpMyFAQ\Core\Json;
use phpMyFAQ\Queue\Message\QueueMessageInterface;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use RuntimeException;
use Throwable;

class Worker
{
    private const int MAX_RETRIES = 3;

    /** @var array<string, callable> */
    private array $handlers = [];

    /**
     * @param int $reservationTimeout Seconds after which a reserved job counts as interrupted and
     *   is made available again. It has to be longer than the longest job runs, otherwise a job
     *   that is still being handled gets handled a second time.
     */
    public function __construct(
        private readonly DatabaseTransport $databaseTransport,
        private readonly int $reservationTimeout = 900,
    ) {
        if ($reservationTimeout < 1) {
            throw new InvalidArgumentException(sprintf(
                'The queue reservation timeout must be at least 1 second, got %d.',
                $reservationTimeout,
            ));
        }
    }

    public function registerHandler(string $messageClass, callable $handler): void
    {
        $this->handlers[$messageClass] = $handler;
    }

    /**
     * Runs one available job, from the given queue or from any queue if none is given.
     */
    public function runOnce(?string $queue = null): bool
    {
        $job = $this->databaseTransport->reserve($queue);
        if ($job === null) {
            return false;
        }

        $jobId = (int) $job['id'];
        $headers = $job['headers'];

        try {
            $message = $this->decodeMessage($job['body']);
            $handler = $this->handlers[$message::class] ?? null;

            if (!is_callable($handler)) {
                throw new RuntimeException('No queue handler registered for message class: ' . $message::class);
            }

            $handler($message);
            $this->databaseTransport->acknowledge($jobId);

            return true;
        } catch (Throwable $throwable) {
            $attempts = (int) ($headers['attempts'] ?? 0) + 1;
            $headers['attempts'] = $attempts;

            $this->logFailedAttempt(
                $jobId,
                $job['body'],
                $attempts,
                sprintf(
                    '%s: %s in %s:%d',
                    $throwable::class,
                    $throwable->getMessage(),
                    $throwable->getFile(),
                    $throwable->getLine(),
                ),
            );

            if ($attempts >= self::MAX_RETRIES) {
                $this->databaseTransport->acknowledge($jobId);
                return true;
            }

            $this->databaseTransport->release($jobId, new DateTimeImmutable('+60 seconds'), $headers);
            return true;
        }
    }

    /**
     * Makes jobs available again whose worker ended while handling them (execution time or memory
     * limit, fatal error, killed process), so nothing acknowledged or released them. The
     * interrupted run counts as a failed attempt, so a job that takes the worker down every time
     * is discarded like any other failing job instead of being retried forever.
     *
     * @return int Number of interrupted jobs found
     */
    public function releaseStaleReservations(): int
    {
        $reservedBefore = new DateTimeImmutable(sprintf('-%d seconds', $this->reservationTimeout));
        $staleJobs = $this->databaseTransport->findStaleReservations($reservedBefore);

        foreach ($staleJobs as $job) {
            $headers = $job['headers'];
            $attempts = (int) ($headers['attempts'] ?? 0) + 1;
            $headers['attempts'] = $attempts;

            $this->logFailedAttempt(
                $job['id'],
                $job['body'],
                $attempts,
                sprintf(
                    'the worker ended while handling it and the reservation is older than %d seconds',
                    $this->reservationTimeout,
                ),
            );

            if ($attempts >= self::MAX_RETRIES) {
                $this->databaseTransport->acknowledge($job['id']);
                continue;
            }

            $this->databaseTransport->releaseStaleReservation($job['id'], $reservedBefore, $headers);
        }

        return count($staleJobs);
    }

    /**
     * Runs until no job is left or the maximum number of jobs has been processed.
     * Processes every queue unless a queue name is given.
     */
    public function run(int $maxJobs = 0, ?string $queue = null): int
    {
        $processed = 0;

        try {
            $this->releaseStaleReservations();
        } catch (Throwable $exception) {
            error_log(sprintf(
                'Queue worker error in run() while releasing stale reservations: %s in %s:%d',
                $exception->getMessage(),
                $exception->getFile(),
                $exception->getLine(),
            ));
        }

        while ($maxJobs === 0 || $processed < $maxJobs) {
            try {
                if (!$this->runOnce($queue)) {
                    break;
                }

                ++$processed;
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Queue worker error in run() while processing %s: %s in %s:%d',
                    $queue === null ? 'all queues' : sprintf('queue "%s"', $queue),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                ));
            }
        }

        return $processed;
    }

    private function logFailedAttempt(int $jobId, string $body, int $attempts, string $reason): void
    {
        error_log(sprintf(
            'Queue job %d (%s) failed on attempt %d of %d, %s: %s',
            $jobId,
            $this->messageClassOf($body),
            $attempts,
            self::MAX_RETRIES,
            $attempts >= self::MAX_RETRIES ? 'job discarded' : 'job will be retried',
            $reason,
        ));
    }

    /**
     * Reads the message class for the log, also from a body that decodeMessage() rejects.
     */
    private function messageClassOf(string $body): string
    {
        $messageClass = Json::decodeAssoc($body)['class'] ?? null;

        return is_string($messageClass) ? $messageClass : 'unknown message class';
    }

    private function decodeMessage(string $body): QueueMessageInterface
    {
        $decoded = Json::decodeAssoc($body);
        if ($decoded === null || !array_key_exists('class', $decoded)) {
            throw new RuntimeException('Queue job body has an invalid format.');
        }

        $messageClass = (string) $decoded['class'];
        $rawPayload = is_array($decoded['payload'] ?? null) ? $decoded['payload'] : [];
        $payload = [];
        foreach ($rawPayload as $payloadKey => $payloadValue) {
            $payload[(string) $payloadKey] = $payloadValue;
        }

        if (!class_exists($messageClass)) {
            throw new RuntimeException('Queue job references unknown message class: ' . $messageClass);
        }

        if (!is_subclass_of($messageClass, QueueMessageInterface::class)) {
            throw new RuntimeException(
                'Queue message class ' . $messageClass . ' does not implement ' . QueueMessageInterface::class,
            );
        }

        /* @mago-expect analysis:possibly-static-access-on-interface - is_subclass_of() excludes the interface itself */
        return $messageClass::fromArray($payload);
    }
}
