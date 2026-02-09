<?php

/**
 * OAuth2 client entity.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-09
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\OAuth2\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

final class ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    public ?string $secret = null {
        get {
            return $this->secret;
        }
        set {
            $this->secret = $value;
        }
    }

    /** @var string[] */
    public array $allowedGrants = [] {
        set {
            $this->allowedGrants = $value;
        }
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param string|string[] $redirectUri
     */
    public function setRedirectUri(string|array $redirectUri): void
    {
        $this->redirectUri = $redirectUri;
    }

    public function setConfidential(bool $isConfidential): void
    {
        $this->isConfidential = $isConfidential;
    }

    public function supportsGrantType(string $grantType): bool
    {
        if ($this->allowedGrants === []) {
            return true;
        }

        return in_array($grantType, $this->allowedGrants, true);
    }
}
