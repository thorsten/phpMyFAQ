<?php

/**
 * Vote entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-29
 */

namespace phpMyFAQ\Entity;

use DateTime;

class Vote
{
    private int $id;

    private int $faqId;

    private int $vote;

    private int $users;

    private DateTime $createdAt;

    private string $ip;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): Vote
    {
        $this->id = $id;
        return $this;
    }

    public function getFaqId(): int
    {
        return $this->faqId;
    }

    public function setFaqId(int $faqId): Vote
    {
        $this->faqId = $faqId;
        return $this;
    }

    public function getVote(): int
    {
        return $this->vote;
    }

    public function setVote(int $vote): Vote
    {
        $this->vote = $vote;
        return $this;
    }

    public function getUsers(): int
    {
        return $this->users;
    }

    public function setUsers(int $users): Vote
    {
        $this->users = $users;
        return $this;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTime $createdAt): Vote
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): Vote
    {
        $this->ip = $ip;
        return $this;
    }
}
