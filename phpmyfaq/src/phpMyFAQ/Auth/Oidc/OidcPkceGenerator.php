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

use InvalidArgumentException;

final class OidcPkceGenerator
{
    public function generateVerifier(int $length = 128): string
    {
        if ($length < 43 || $length > 128) {
            throw new InvalidArgumentException(sprintf(
                'PKCE verifier length must be between 43 and 128, got %d',
                $length,
            ));
        }

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
        $len = strlen($verifier);
        if ($len < 43 || $len > 128) {
            throw new InvalidArgumentException(sprintf(
                'PKCE verifier length must be between 43 and 128, got %d',
                $len,
            ));
        }

        if (preg_match('/[^0-9a-zA-Z\-._~]/', $verifier) === 1) {
            throw new InvalidArgumentException('PKCE verifier contains invalid characters');
        }

        return rtrim(
            strtr(base64_encode(hash(algo: 'sha256', data: $verifier, binary: true)), from: '+/', to: '-_'),
            characters: '=',
        );
    }
}
