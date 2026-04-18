<?php

/**
 * OIDC client configuration.
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
 * @since     2026-04-18
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\Oidc;

use SensitiveParameter;

final readonly class OidcClientConfig
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public string $clientId,
        #[SensitiveParameter]
        public string $clientSecret,
        public string $redirectUri,
        public array $scopes,
    ) {
    }

    public function getScopesAsString(): string
    {
        return implode(' ', $this->scopes);
    }
}
