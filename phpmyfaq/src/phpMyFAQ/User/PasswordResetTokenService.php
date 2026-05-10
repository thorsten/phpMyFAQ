<?php

/**
 * Issues and verifies signed password reset tokens.
 *
 * The token is an HMAC-SHA256 over "{userId}|{expires}" keyed by the user's
 * current encrypted password. Because the key changes the moment the password
 * is updated, every previously issued token is implicitly invalidated. This
 * gives single-use semantics without any additional state.
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

final class PasswordResetTokenService
{
    public const int DEFAULT_LIFETIME_SECONDS = 3600;

    public const int MAX_LIFETIME_SECONDS = 86_400;

    /**
     * @return array{userId: int, expires: int, signature: string}
     */
    public function issue(int $userId, string $passwordKey, ?int $lifetimeSeconds = null): array
    {
        $lifetime = $lifetimeSeconds ?? self::DEFAULT_LIFETIME_SECONDS;
        if ($lifetime < 60 || $lifetime > self::MAX_LIFETIME_SECONDS) {
            $lifetime = self::DEFAULT_LIFETIME_SECONDS;
        }

        $expires = time() + $lifetime;

        return [
            'userId' => $userId,
            'expires' => $expires,
            'signature' => $this->sign($userId, $expires, $passwordKey),
        ];
    }

    public function verify(int $userId, int $expires, string $signature, string $passwordKey): bool
    {
        if ($userId <= 0 || $expires <= 0 || $signature === '' || $passwordKey === '') {
            return false;
        }

        $now = time();
        if ($expires < $now) {
            return false;
        }

        if ($expires > ($now + self::MAX_LIFETIME_SECONDS)) {
            return false;
        }

        $expected = $this->sign($userId, $expires, $passwordKey);

        return hash_equals($expected, $signature);
    }

    private function sign(int $userId, int $expires, string $passwordKey): string
    {
        return hash_hmac('sha256', $userId . '|' . $expires, $passwordKey);
    }
}
