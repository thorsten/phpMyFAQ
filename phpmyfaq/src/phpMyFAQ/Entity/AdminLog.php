<?php

/**
 * Adminlog entity class.
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
 * @since     2024-03-24
 */

namespace phpMyFAQ\Entity;

class AdminLog
{
    private int $id;

    private int $time;

    private int $userId;

    private string $text;

    private string $ip;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): AdminLog
    {
        $this->id = $id;
        return $this;
    }

    public function getTime(): int
    {
        return $this->time;
    }

    public function setTime(int $time): AdminLog
    {
        $this->time = $time;
        return $this;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): AdminLog
    {
        $this->userId = $userId;
        return $this;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function setText(string $text): AdminLog
    {
        $this->text = $text;
        return $this;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function setIp(string $ip): AdminLog
    {
        $this->ip = $ip;
        return $this;
    }
}
