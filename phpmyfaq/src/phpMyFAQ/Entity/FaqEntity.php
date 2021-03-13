<?php

/**
 * The FAQ entity class.
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2020-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
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
    private $id;

    /** @var string The language of the FAQ */
    private $language;

    /** @var int The unique solution ID of the FAQ */
    private $solutionId;

    /** @var int The current revision ID of the FAQ */
    private $revisionId;

    /** @var bool The active flag of the FAQ */
    private $active;

    /** @var bool The sticky flag of the FAQ */
    private $sticky;

    /** @var string The keywords of the FAQ as comma separated string */
    private $keywords;

    /** @var string The question of the FAQ */
    private $question;

    /** @var string The answer of the FAQ */
    private $answer;

    /** @var string The name of the FAQ author */
    private $author;

    /** @var string The email address of the FAQ author */
    private $email;

    /** @var bool The flag if comments are allowed */
    private $comment;

    /** @var string Notes about the FAQ, only visible in the admin backend */
    private $notes;

    /** @var string The state if the links: "nolinks", "linkok" or "linkbad" */
    private $linkState;

    /** @var DateTime The date of the last verification of the links */
    private $linksCheckedDate;

    /** @var DateTime The date from which the FAQ is valid */
    private $validFrom;

    /** @var DateTime The date until which the FAQ is valid */
    private $validTo;

    /** @var DateTime The date when the FAQ was created */
    private $createdDate;

    /** @var DateTime The date when the FAQ was updated the last time */
    private $updatedDate;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return FaqEntity
     */
    public function setId(int $id): FaqEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return FaqEntity
     */
    public function setLanguage(string $language): FaqEntity
    {
        $this->language = $language;
        return $this;
    }

    /**
     * @return int
     */
    public function getSolutionId(): int
    {
        return $this->solutionId;
    }

    /**
     * @param int $solutionId
     * @return FaqEntity
     */
    public function setSolutionId(int $solutionId): FaqEntity
    {
        $this->solutionId = $solutionId;
        return $this;
    }

    /**
     * @return int
     */
    public function getRevisionId(): int
    {
        return $this->revisionId;
    }

    /**
     * @param int $revisionId
     * @return FaqEntity
     */
    public function setRevisionId(int $revisionId): FaqEntity
    {
        $this->revisionId = $revisionId;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return FaqEntity
     */
    public function setActive(bool $active): FaqEntity
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * @param bool $sticky
     * @return FaqEntity
     */
    public function setSticky(bool $sticky): FaqEntity
    {
        $this->sticky = $sticky;
        return $this;
    }

    /**
     * @return string
     */
    public function getKeywords(): string
    {
        return $this->keywords;
    }

    /**
     * @param string $keywords
     * @return FaqEntity
     */
    public function setKeywords(string $keywords): FaqEntity
    {
        $this->keywords = $keywords;
        return $this;
    }

    /**
     * @return string
     */
    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @param string $question
     * @return FaqEntity
     */
    public function setQuestion(string $question): FaqEntity
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string
     */
    public function getAnswer(): string
    {
        return $this->answer;
    }

    /**
     * @param string $answer
     * @return FaqEntity
     */
    public function setAnswer(string $answer): FaqEntity
    {
        $this->answer = $answer;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        return $this->author;
    }

    /**
     * @param string $author
     * @return FaqEntity
     */
    public function setAuthor(string $author): FaqEntity
    {
        $this->author = $author;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return FaqEntity
     */
    public function setEmail(string $email): FaqEntity
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComment(): bool
    {
        return $this->comment;
    }

    /**
     * @param bool $comment
     * @return FaqEntity
     */
    public function setComment(bool $comment): FaqEntity
    {
        $this->comment = $comment;
        return $this;
    }

    /**
     * @return string
     */
    public function getNotes(): string
    {
        return $this->notes;
    }

    /**
     * @param string $notes
     * @return FaqEntity
     */
    public function setNotes(string $notes): FaqEntity
    {
        $this->notes = $notes;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkState(): string
    {
        return $this->linkState;
    }

    /**
     * @param string $linkState
     * @return FaqEntity
     */
    public function setLinkState(string $linkState): FaqEntity
    {
        $this->linkState = $linkState;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getLinksCheckedDate(): DateTime
    {
        if ($this->linksCheckedDate instanceof DateTime) {
            return $this->linksCheckedDate;
        } else {
            return $this->linksCheckedDate = new DateTime();
        }
    }

    /**
     * @param DateTime $linksCheckedDate
     * @return FaqEntity
     */
    public function setLinksCheckedDate(DateTime $linksCheckedDate): FaqEntity
    {
        $this->linksCheckedDate = $linksCheckedDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getValidFrom(): DateTime
    {
        if ($this->validFrom instanceof DateTime) {
            return $this->validFrom;
        } else {
            return $this->validFrom = new DateTime();
        }
    }

    /**
     * @param DateTime $validFrom
     * @return FaqEntity
     */
    public function setValidFrom(DateTime $validFrom): FaqEntity
    {
        $this->validFrom = $validFrom;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getValidTo(): DateTime
    {
        if ($this->validTo instanceof DateTime) {
            return $this->validTo;
        } else {
            return $this->validTo = new DateTime();
        }
    }

    /**
     * @param DateTime $validTo
     * @return FaqEntity
     */
    public function setValidTo(DateTime $validTo): FaqEntity
    {
        $this->validTo = $validTo;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getCreatedDate(): DateTime
    {
        if ($this->createdDate instanceof DateTime) {
            return $this->createdDate;
        } else {
            return $this->createdDate = new DateTime();
        }
    }

    /**
     * @param DateTime $createdDate
     * @return FaqEntity
     */
    public function setCreatedDate(DateTime $createdDate): FaqEntity
    {
        $this->createdDate = $createdDate;
        return $this;
    }

    /**
     * @return DateTime
     */
    public function getUpdatedDate(): DateTime
    {
        if ($this->updatedDate instanceof DateTime) {
            return $this->updatedDate;
        } else {
            return $this->updatedDate = new DateTime();
        }
    }

    /**
     * @param DateTime $updatedDate
     * @return FaqEntity
     */
    public function setUpdatedDate(DateTime $updatedDate): FaqEntity
    {
        $this->updatedDate = $updatedDate;
        return $this;
    }
}
