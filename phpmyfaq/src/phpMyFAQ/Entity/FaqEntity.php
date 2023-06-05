<?php

/**
 * The FAQ entity class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2020-11-01
 */

namespace phpMyFAQ\Entity;

use DateTime;

/**
 * Class FaqEntity
 *
 * @package phpMyFAQ\Entity
 */
class FaqEntity
{
    private ?int $id = null;

    private ?string $language = null;

    private ?int $solutionId = null;

    private ?int $revisionId = null;

    private ?bool $active = null;

    private ?bool $sticky = null;

    private ?string $keywords = null;

    private ?string $question = null;

    private ?string $answer = null;

    private ?string $author = null;

    private ?string $email = null;

    private ?bool $comment = null;

    private ?string $notes = null;

    private ?DateTime $validFrom = null;

    private ?DateTime $validTo = null;

    private ?DateTime $createdDate = null;

    private ?DateTime $updatedDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): FaqEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): FaqEntity
    {
        $this->language = $language;
        return $this;
    }

    public function getSolutionId(): int
    {
        return $this->solutionId;
    }

    public function setSolutionId(int $solutionId): FaqEntity
    {
        $this->solutionId = $solutionId;
        return $this;
    }

    public function getRevisionId(): int
    {
        return $this->revisionId;
    }

    public function setRevisionId(int $revisionId): FaqEntity
    {
        $this->revisionId = $revisionId;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): FaqEntity
    {
        $this->active = $active;
        return $this;
    }

    public function isSticky(): bool
    {
        return $this->sticky;
    }

    public function setSticky(bool $sticky): FaqEntity
    {
        $this->sticky = $sticky;
        return $this;
    }

    public function getKeywords(): string
    {
        return $this->keywords;
    }

    public function setKeywords(string $keywords): FaqEntity
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): FaqEntity
    {
        $this->question = $question;
        return $this;
    }

    public function getAnswer(): string
    {
        return $this->answer;
    }

    public function setAnswer(string $answer): FaqEntity
    {
        $this->answer = $answer;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): FaqEntity
    {
        $this->author = $author;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): FaqEntity
    {
        $this->email = $email;
        return $this;
    }

    public function isComment(): bool
    {
        return $this->comment;
    }

    public function setComment(bool $comment): FaqEntity
    {
        $this->comment = $comment;
        return $this;
    }

    public function getNotes(): string
    {
        return $this->notes;
    }

    public function setNotes(string $notes): FaqEntity
    {
        $this->notes = $notes;
        return $this;
    }

    public function getValidFrom(): DateTime
    {
        if ($this->validFrom instanceof DateTime) {
            return $this->validFrom;
        } else {
            return $this->validFrom = new DateTime();
        }
    }

    public function setValidFrom(DateTime $validFrom): FaqEntity
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    public function getValidTo(): DateTime
    {
        if ($this->validTo instanceof DateTime) {
            return $this->validTo;
        } else {
            return $this->validTo = new DateTime('99991231235959');
        }
    }

    public function setValidTo(DateTime $validTo): FaqEntity
    {
        $this->validTo = $validTo;
        return $this;
    }

    public function getCreatedDate(): DateTime
    {
        if ($this->createdDate instanceof DateTime) {
            return $this->createdDate;
        } else {
            return $this->createdDate = new DateTime();
        }
    }

    public function setCreatedDate(DateTime $createdDate): FaqEntity
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    public function getUpdatedDate(): DateTime
    {
        if ($this->updatedDate instanceof DateTime) {
            return $this->updatedDate;
        } else {
            return $this->updatedDate = new DateTime();
        }
    }

    public function setUpdatedDate(DateTime $updatedDate): FaqEntity
    {
        $this->updatedDate = $updatedDate;
        return $this;
    }
}
