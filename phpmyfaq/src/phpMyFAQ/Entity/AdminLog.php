<?php

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
