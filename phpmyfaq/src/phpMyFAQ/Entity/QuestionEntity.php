<?php

/**
 * The QuestionEntity class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Entity;

/**
 * Class QuestionEntity
 *
 * @package phpMyFAQ\Entity
 */
class QuestionEntity
{
    public int $id;

    public string $language;

    public string $username;

    public string $email;

    public int $categoryId;

    public string $question;

    public string $created;

    public int $answerId;

    public bool $isVisible;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): QuestionEntity
    {
        $this->id = $id;
        return $this;
    }

    public function getLanguage(): string
    {
        return $this->language;
    }

    public function setLanguage(string $language): QuestionEntity
    {
        $this->language = $language;
        return $this;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): QuestionEntity
    {
        $this->username = $username;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): QuestionEntity
    {
        $this->email = $email;
        return $this;
    }

    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    public function setCategoryId(int $categoryId): QuestionEntity
    {
        $this->categoryId = $categoryId;
        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): QuestionEntity
    {
        $this->question = $question;
        return $this;
    }

    public function getCreated(): string
    {
        return $this->created;
    }

    public function setCreated(string $created): QuestionEntity
    {
        $this->created = $created;
        return $this;
    }

    public function getAnswerId(): int
    {
        return $this->answerId;
    }

    public function setAnswerId(int $answerId): QuestionEntity
    {
        $this->answerId = $answerId;
        return $this;
    }

    public function isVisible(): bool
    {
        return $this->isVisible;
    }

    public function setIsVisible(bool $isVisible): QuestionEntity
    {
        $this->isVisible = $isVisible;
        return $this;
    }
}
