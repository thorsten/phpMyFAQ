<?php

/**
 * Issues and verifies password reset tokens.
 *
 * A token is a random 256-bit secret that is mailed to the user. Only its
 * SHA-256 hash and its expiry are stored on the user row, so neither a copy of
 * the database nor the user's stored password hash lets anyone reproduce a
 * valid reset link. Issuing a new token replaces the stored hash, and a
 * password change clears it, which gives single-use semantics.
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
 * @since     2026-05-10
 */

declare(strict_types=1);

namespace phpMyFAQ\User;

use phpMyFAQ\User;
use Random\RandomException;

final class PasswordResetTokenService
{
    public const int DEFAULT_LIFETIME_SECONDS = 3600;

    public const int MAX_LIFETIME_SECONDS = 86_400;

    private const int TOKEN_BYTES = 32;

    /**
     * Generates a fresh token for the loaded user and stores its hash. Any token issued
     * earlier for the same user stops working.
     *
     * @return array{userId: int, expires: int, signature: string}
     * @throws UserException when the token hash cannot be stored
     * @throws RandomException
     */
    public function issue(User $user, ?int $lifetimeSeconds = null): array
    {
        $lifetime = $lifetimeSeconds ?? self::DEFAULT_LIFETIME_SECONDS;
        if ($lifetime < 60 || $lifetime > self::MAX_LIFETIME_SECONDS) {
            $lifetime = self::DEFAULT_LIFETIME_SECONDS;
        }

        $expires = time() + $lifetime;
        $token = bin2hex(random_bytes(self::TOKEN_BYTES));

        if (!$user->storePasswordResetToken($this->hash($token), $expires)) {
            throw new UserException('Could not store the password reset token for user ' . $user->getUserId());
        }

        return [
            'userId' => $user->getUserId(),
            'expires' => $expires,
            'signature' => $token,
        ];
    }

    /**
     * Accepts the token only when its hash and expiry match what was stored for the loaded
     * user and the expiry has not passed.
     */
    public function verify(User $user, int $expires, string $signature): bool
    {
        if ($expires <= 0 || $signature === '') {
            return false;
        }

        $now = time();
        if ($expires < $now) {
            return false;
        }

        if ($expires > ($now + self::MAX_LIFETIME_SECONDS)) {
            return false;
        }

        $stored = $user->getPasswordResetToken();
        if ($stored['hash'] === '' || $stored['expires'] !== $expires) {
            return false;
        }

        return hash_equals($stored['hash'], $this->hash($signature));
    }

    private function hash(#[\SensitiveParameter] string $token): string
    {
        return hash('sha256', $token);
    }
}
