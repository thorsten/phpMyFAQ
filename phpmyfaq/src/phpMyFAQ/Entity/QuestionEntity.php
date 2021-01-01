<?php

/**
 * The QuestionEntity class
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ\Entity
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2019-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2019-11-22
 */

namespace phpMyFAQ\Entity;

/**
 * Class QuestionEntity
 *
 * @package phpMyFAQ\Entity
 */
class QuestionEntity
{
    /**
     * @var int
     */
    public $id;

    /**
     * @var string
     */
    public $lang;

    /**
     * @var string
     */
    public $username;

    /**
     * @var string
     */
    public $email;

    /**
     * @var int
     */
    public $categoryId;

    /**
     * @var string
     */
    public $question;

    /**
     * @var string
     */
    public $created;

    /**
     * @var int
     */
    public $answerId;

    /**
     * @var string
     */
    public $isVisible;

    /**
     * QuestionEntity constructor.
     */
    public function __construct()
    {
        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param  int $id
     * @return QuestionEntity
     */
    public function setId(int $id): QuestionEntity
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getLang(): string
    {
        return $this->lang;
    }

    /**
     * @param  string $lang
     * @return QuestionEntity
     */
    public function setLang(string $lang): QuestionEntity
    {
        $this->lang = $lang;
        return $this;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param  string $username
     * @return QuestionEntity
     */
    public function setUsername(string $username): QuestionEntity
    {
        $this->username = $username;
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
     * @param  string $email
     * @return QuestionEntity
     */
    public function setEmail(string $email): QuestionEntity
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int
     */
    public function getCategoryId(): int
    {
        return $this->categoryId;
    }

    /**
     * @param  int $categoryId
     * @return QuestionEntity
     */
    public function setCategoryId(int $categoryId): QuestionEntity
    {
        $this->categoryId = $categoryId;
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
     * @param  string $question
     * @return QuestionEntity
     */
    public function setQuestion(string $question): QuestionEntity
    {
        $this->question = $question;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreated(): string
    {
        return $this->created;
    }

    /**
     * @param  string $created
     * @return QuestionEntity
     */
    public function setCreated(string $created): QuestionEntity
    {
        $this->created = $created;
        return $this;
    }

    /**
     * @return int
     */
    public function getAnswerId(): int
    {
        return $this->answerId;
    }

    /**
     * @param  int $answerId
     * @return QuestionEntity
     */
    public function setAnswerId(int $answerId): QuestionEntity
    {
        $this->answerId = $answerId;
        return $this;
    }

    /**
     * @return string
     */
    public function isVisible(): string
    {
        return $this->isVisible;
    }

    /**
     * @param  string $isVisible
     * @return QuestionEntity
     */
    public function setIsVisible(string $isVisible): QuestionEntity
    {
        $this->isVisible = $isVisible;
        return $this;
    }
}
