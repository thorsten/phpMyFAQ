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
use phpMyFAQ\Queue\Message\QueueMessageInterface;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use RuntimeException;
use Throwable;

class Worker
{
    private const int MAX_RETRIES = 3;

    /** @var array<string, callable> */
    private array $handlers = [];

    public function __construct(
        private readonly DatabaseTransport $databaseTransport,
    ) {
    }

    public function registerHandler(string $messageClass, callable $handler): void
    {
        $this->handlers[$messageClass] = $handler;
    }

    /**
     * Runs one available job.
     */
    public function runOnce(string $queue = 'default'): bool
    {
        $job = $this->databaseTransport->reserve($queue);
        if ($job === null) {
            return false;
        }

        $jobId = (int) $job['id'];
        $headers = $job['headers'];

        try {
            $message = $this->decodeMessage((string) $job['body']);
            $handler = $this->handlers[$message::class] ?? null;

            if (!is_callable($handler)) {
                throw new RuntimeException('No queue handler registered for message class: ' . $message::class);
            }

            $handler($message);
            $this->databaseTransport->acknowledge($jobId);

            return true;
        } catch (Throwable) {
            $attempts = (int) ($headers['attempts'] ?? 0) + 1;
            $headers['attempts'] = $attempts;

            if ($attempts >= self::MAX_RETRIES) {
                $this->databaseTransport->acknowledge($jobId);
            } else {
                $this->databaseTransport->release($jobId, new DateTimeImmutable('+60 seconds'), $headers);
            }

            return true;
        }
    }

    /**
     * Runs until the queue is empty or the maximum number of jobs has been processed.
     */
    public function run(int $maxJobs = 0, string $queue = 'default'): int
    {
        $processed = 0;

        while ($maxJobs === 0 || $processed < $maxJobs) {
            try {
                if (!$this->runOnce($queue)) {
                    break;
                }

                ++$processed;
            } catch (Throwable $exception) {
                error_log(sprintf(
                    'Queue worker error in run() while processing queue "%s": %s in %s:%d',
                    $queue,
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine(),
                ));
            }
        }

        return $processed;
    }

    private function decodeMessage(string $body): QueueMessageInterface
    {
        $decoded = json_decode($body, true);
        if (!is_array($decoded) || !isset($decoded['class'])) {
            throw new RuntimeException('Queue job body has an invalid format.');
        }

        $messageClass = (string) $decoded['class'];
        $payload = is_array($decoded['payload'] ?? null) ? $decoded['payload'] : [];

        if (!class_exists($messageClass)) {
            throw new RuntimeException('Queue job references unknown message class: ' . $messageClass);
        }

        if (!is_subclass_of($messageClass, QueueMessageInterface::class)) {
            throw new RuntimeException('Queue message class '
            . $messageClass
            . ' does not implement '
            . QueueMessageInterface::class);
        }

        /** @var class-string<QueueMessageInterface> $messageClass */
        return $messageClass::fromArray($payload);
    }
}
