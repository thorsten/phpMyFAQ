<?php

/**
 * The Comment class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-12-28
 */

namespace phpMyFAQ\Entity;

/**
 * Class Comment
 * @package phpMyFAQ\Entity
 */
class Comment
{
    public int $id;

    public int $recordId;

    public int $categoryId;

    public string $type;

    public string $username;

    public string $email;

    public string $comment;

    public string $date;

    public ?bool $helped = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Comment
    {
        $this->id = $id;
        return $this;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function setRecordId(int $recordId): Comment
    {
        $this->recordId = $recordId;
        return $this;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): Comment
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): Comment
    {
        $this->type = $type;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): Comment
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): Comment
    {
        $this->email = $email;
        return $this;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment): Comment
    {
        $this->comment = $comment;
        return $this;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function setDate(string $date): Comment
    {
        $this->date = $date;
        return $this;
    }

    public function hasHelped(): ?bool
    {
        return $this->helped;
    }

    public function setHelped(bool $helped): Comment
    {
        $this->helped = $helped;
        return $this;
    }
}
