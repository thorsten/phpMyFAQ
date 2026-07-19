<?php

/**
 * Password hashing and verification for database authentication.
 *
 * Writes bcrypt hashes and verifies both bcrypt and legacy salted SHA-256
 * hashes, enabling transparent migration of legacy passwords on login.
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
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use phpMyFAQ\Configuration;
use SensitiveParameter;

final class PasswordHasher
{
    public function __construct(
        private readonly Configuration $configuration,
    ) {
    }

    /**
     * Hashes a password for storage using bcrypt.
     */
    public function hash(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifies a password against a stored hash, accepting both bcrypt and
     * legacy salted SHA-256 hashes.
     */
    public function verify(string $login, #[SensitiveParameter] string $password, string $storedHash): bool
    {
        if ($this->isLegacyHash($storedHash)) {
            return hash_equals($storedHash, $this->legacyHash($login, $password));
        }

        return password_verify($password, $storedHash);
    }

    /**
     * Returns true when the stored hash should be upgraded to current bcrypt
     * parameters (legacy SHA-256, or bcrypt with outdated cost).
     */
    public function needsRehash(string $storedHash): bool
    {
        if ($this->isLegacyHash($storedHash)) {
            return true;
        }

        return password_needs_rehash($storedHash, PASSWORD_BCRYPT);
    }

    private function isLegacyHash(string $storedHash): bool
    {
        // password_get_info() reports algo === null for non-PHC strings,
        // i.e. the 64-char salted SHA-256 hex hashes produced by the old scheme.
        return password_get_info($storedHash)['algo'] === null;
    }

    private function legacyHash(string $login, #[SensitiveParameter] string $password): string
    {
        $salt = (string) $this->configuration->get('security.salt') . $login;
        return hash('sha256', $password . $salt);
    }
}
