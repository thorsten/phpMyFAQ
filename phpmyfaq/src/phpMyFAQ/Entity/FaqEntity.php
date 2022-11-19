<?php

/**
 * The FAQ entity class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2022 phpMyFAQ Team
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
    /** @var int The primary key of the FAQ */
    private ?int $id = null;

    /** @var string The language of the FAQ */
    private ?string $language = null;

    /** @var int The unique solution ID of the FAQ */
    private ?int $solutionId = null;

    /** @var int The current revision ID of the FAQ */
    private ?int $revisionId = null;

    /** @var bool The active flag of the FAQ */
    private ?bool $active = null;

    /** @var bool The sticky flag of the FAQ */
    private ?bool $sticky = null;

    /** @var string The keywords of the FAQ as comma separated string */
    private ?string $keywords = null;

    /** @var string The question of the FAQ */
    private ?string $question = null;

    /** @var string The answer of the FAQ */
    private ?string $answer = null;

    /** @var string The name of the FAQ author */
    private ?string $author = null;

    /** @var string The email address of the FAQ author */
    private ?string $email = null;

    /** @var bool The flag if comments are allowed */
    private ?bool $comment = null;

    /** @var string Notes about the FAQ, only visible in the admin backend */
    private ?string $notes = null;

    /** @var string The state if the links: "nolinks", "linkok" or "linkbad" */
    private ?string $linkState = null;

    /** @var DateTime The date of the last verification of the links */
    private ?\DateTime $linksCheckedDate = null;

    /** @var DateTime The date from which the FAQ is valid */
    private ?\DateTime $validFrom = null;

    /** @var DateTime The date until which the FAQ is valid */
    private ?\DateTime $validTo = null;

    /** @var DateTime The date when the FAQ was created */
    private ?\DateTime $createdDate = null;

    /** @var DateTime The date when the FAQ was updated the last time */
    private ?\DateTime $updatedDate = null;

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

    public function getLinkState(): string
    {
        return $this->linkState;
    }

    public function setLinkState(string $linkState): FaqEntity
    {
        $this->linkState = $linkState;
        return $this;
    }

    public function getLinksCheckedDate(): DateTime
    {
        if ($this->linksCheckedDate instanceof DateTime) {
            return $this->linksCheckedDate;
        } else {
            return $this->linksCheckedDate = new DateTime();
        }
    }

    public function setLinksCheckedDate(DateTime $linksCheckedDate): FaqEntity
    {
        $this->linksCheckedDate = $linksCheckedDate;
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
            return $this->validTo = new DateTime();
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
