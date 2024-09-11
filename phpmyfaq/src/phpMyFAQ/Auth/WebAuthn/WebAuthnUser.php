<?php

namespace phpMyFAQ\Auth\WebAuthn;

class WebAuthnUser
{
    private string $id;
    private string $name;
    private string $webAuthnKeys;

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): WebAuthnUser
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): WebAuthnUser
    {
        $this->name = $name;
        return $this;
    }

    public function getWebAuthnKeys(): string
    {
        return $this->webAuthnKeys;
    }

    public function setWebAuthnKeys(string $webAuthnKeys): WebAuthnUser
    {
        $this->webAuthnKeys = $webAuthnKeys;
        return $this;
    }
}
