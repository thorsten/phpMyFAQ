<?php

/**
 * Entity class for WebAuthn user.
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
 * @since     2024-11-09
 */

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
