<?php

/**
 * The question history entity
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-08-15
 */

declare(strict_types=1);

namespace phpMyFAQ\Entity;

use InvalidArgumentException;
use phpMyFAQ\Enums\QuestionHistoryEventType;

final readonly class QuestionHistoryEntity
{
    public function __construct(
        private int $questionId,
        private string $questionLanguage,
        private QuestionHistoryEventType $eventType,
        private int $userId,
        private string $username,
        private int $faqId = 0,
        private ?string $created = null,
    ) {
        if ($this->questionId <= 0) {
            throw new InvalidArgumentException('Question id must be a positive integer.');
        }

        if ($this->questionLanguage === '') {
            throw new InvalidArgumentException('Question language must not be empty.');
        }

        if ($this->username === '') {
            throw new InvalidArgumentException('Username must not be empty.');
        }
    }

    public function getQuestionId(): int
    {
        return $this->questionId;
    }

    public function getQuestionLanguage(): string
    {
        return $this->questionLanguage;
    }

    public function getEventType(): QuestionHistoryEventType
    {
        return $this->eventType;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFaqId(): int
    {
        return $this->faqId;
    }

    public function getCreated(): ?string
    {
        return $this->created;
    }
}
