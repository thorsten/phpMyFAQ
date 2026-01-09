<?php

/**
 * Admin log entity class.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2024-2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2024-03-24
 */

declare(strict_types=1);

namespace phpMyFAQ\Entity;

class AdminLog
{
    private int $id = 0;

    private int $time = 0;

    private int $userId = 0;

    private string $text = '';

    private string $ip = '';

    private ?string $hash = null;
    private ?string $previousHash = null;

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

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(?string $hash): AdminLog
    {
        $this->hash = $hash;
        return $this;
    }

    public function getPreviousHash(): ?string
    {
        return $this->previousHash;
    }

    public function setPreviousHash(?string $previousHash): AdminLog
    {
        $this->previousHash = $previousHash;
        return $this;
    }

    /**
     * Calculates SHA-256 hash over all log data.
     * Note: ID is not included because it's auto-generated after INSERT
     * @return string 64-character hex hash
     */
    public function calculateHash(): string
    {
        $data = implode('|', [
            (string) $this->time,
            (string) $this->userId,
            $this->ip,
            $this->text,
            $this->previousHash ?? '',
        ]);

        return hash('sha256', $data);
    }

    /**
     * Verifies if the stored hash matches the calculated hash.
     * @return bool True if the hash is valid, false if tampered
     */
    public function verifyIntegrity(): bool
    {
        if ($this->hash === null) {
            return false;
        }

        $calculatedHash = $this->calculateHash();

        return hash_equals($this->hash, $calculatedHash);
    }
}
