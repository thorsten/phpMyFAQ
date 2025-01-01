<?php

/**
 * The News message model class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-24
 */

namespace phpMyFAQ\Entity;

use DateTime;

class NewsMessage
{
    private int $id;

    private string $language;

    private string $header;

    private string $message;

    private DateTime $created;

    private string $author;

    private string $email;

    private bool $active;

    private bool $comment;

    private ?DateTime $dateStart;

    private ?DateTime $dateEnd;

    private ?string $link;

    private ?string $linkTitle;

    private ?string $linkTarget;

    public function getId(): int
    {
        return $this->id;
    }
    public function setId(int $id): NewsMessage
    {
        $this->id = $id;
        return $this;
    }
    public function getLanguage(): string
    {
        return $this->language;
    }
    public function setLanguage(string $language): NewsMessage
    {
        $this->language = $language;
        return $this;
    }
    public function getHeader(): string
    {
        return $this->header;
    }
    public function setHeader(string $header): NewsMessage
    {
        $this->header = $header;
        return $this;
    }
    public function getMessage(): string
    {
        return $this->message;
    }
    public function setMessage(string $message): NewsMessage
    {
        $this->message = $message;
        return $this;
    }
    public function getCreated(): DateTime
    {
        return $this->created;
    }
    public function setCreated(DateTime $created): NewsMessage
    {
        $this->created = $created;
        return $this;
    }
    public function getAuthor(): string
    {
        return $this->author;
    }
    public function setAuthor(string $author): NewsMessage
    {
        $this->author = $author;
        return $this;
    }
    public function getEmail(): string
    {
        return $this->email;
    }
    public function setEmail(string $email): NewsMessage
    {
        $this->email = $email;
        return $this;
    }
    public function isActive(): bool
    {
        return $this->active;
    }
    public function setActive(bool $active): NewsMessage
    {
        $this->active = $active;
        return $this;
    }
    public function isComment(): bool
    {
        return $this->comment;
    }
    public function setComment(bool $comment): NewsMessage
    {
        $this->comment = $comment;
        return $this;
    }
    public function getDateStart(): ?DateTime
    {
        return $this->dateStart ?? null;
    }
    public function setDateStart(DateTime $dateStart): NewsMessage
    {
        $this->dateStart = $dateStart;
        return $this;
    }
    public function getDateEnd(): ?DateTime
    {
        return $this->dateEnd ?? null;
    }
    public function setDateEnd(DateTime $dateEnd): NewsMessage
    {
        $this->dateEnd = $dateEnd;
        return $this;
    }
    public function getLink(): string
    {
        return $this->link ?? '';
    }
    public function setLink(string $link): NewsMessage
    {
        $this->link = $link;
        return $this;
    }
    public function getLinkTitle(): string
    {
        return $this->linkTitle ?? '';
    }
    public function setLinkTitle(string $linkTitle): NewsMessage
    {
        $this->linkTitle = $linkTitle;
        return $this;
    }
    public function getLinkTarget(): string
    {
        return $this->linkTarget ?? '';
    }
    public function setLinkTarget(string $linkTarget): NewsMessage
    {
        $this->linkTarget = $linkTarget;
        return $this;
    }
}
