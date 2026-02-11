<?php

/**
 * Message for queued exports.
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

final readonly class ExportMessage implements QueueMessageInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        public string $format,
        public int $userId,
        public array $options = [],
    ) {
    }

    public function toArray(): array
    {
        return [
            'format' => $this->format,
            'userId' => $this->userId,
            'options' => $this->options,
        ];
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            format: (string) ($payload['format'] ?? ''),
            userId: (int) ($payload['userId'] ?? 0),
            options: is_array($payload['options'] ?? null) ? $payload['options'] : [],
        );
    }
}
