<?php

/**
 * Message for queued mail delivery.
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

namespace phpMyFAQ\Queue\Message;

final readonly class SendMailMessage implements QueueMessageInterface
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $recipient,
        public string $subject,
        public string $body,
        public array $metadata = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'recipient' => $this->recipient,
            'subject' => $this->subject,
            'body' => $this->body,
            'metadata' => $this->metadata,
        ];
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            recipient: (string) ($payload['recipient'] ?? ''),
            subject: (string) ($payload['subject'] ?? ''),
            body: (string) ($payload['body'] ?? ''),
            metadata: is_array($payload['metadata'] ?? null) ? $payload['metadata'] : [],
        );
    }
}
