<?php

/**
 * Database-backed message bus for background jobs.
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

use phpMyFAQ\Queue\Message\QueueMessageInterface;
use phpMyFAQ\Queue\Transport\DatabaseTransport;
use RuntimeException;

final readonly class DatabaseMessageBus
{
    public function __construct(
        private DatabaseTransport $databaseTransport,
    ) {
    }

    /**
     * @param array<string, mixed> $headers
     * @throws \JsonException
     */
    public function dispatch(object $message, string $queue = 'default', array $headers = []): int
    {
        $payload = $message instanceof QueueMessageInterface ? $message->toArray() : get_object_vars($message);

        $encodedBody = json_encode([
            'class' => $message::class,
            'payload' => $payload,
        ], JSON_THROW_ON_ERROR);

        if (!is_string($encodedBody)) {
            throw new RuntimeException('Unable to encode queue message payload.');
        }

        return $this->databaseTransport->enqueue($encodedBody, $headers, $queue);
    }
}
