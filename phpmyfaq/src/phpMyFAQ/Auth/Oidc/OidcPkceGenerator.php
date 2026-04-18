<?php

/**
 * OIDC PKCE helper.
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

final class OidcPkceGenerator
{
    public function generateVerifier(int $length = 128): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ-._~';
        $charLength = strlen($chars) - 1;
        $verifier = '';

        for ($index = 0; $index < $length; ++$index) {
            $verifier .= $chars[random_int(0, $charLength)];
        }

        return $verifier;
    }

    public function generateChallenge(string $verifier): string
    {
        return rtrim(
            strtr(base64_encode(hash(algo: 'sha256', data: $verifier, binary: true)), from: '+/', to: '-_'),
            characters: '=',
        );
    }
}
