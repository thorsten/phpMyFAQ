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
use phpMyFAQ\Environment;
use SensitiveParameter;

final class PasswordHasher
{
    /**
     * Environment variable that overrides the bcrypt cost factor. The test suite sets it
     * to the bcrypt minimum because it hashes thousands of passwords per run; production
     * leaves it unset and uses PHP's default.
     */
    public const string COST_ENVIRONMENT_VARIABLE = 'PASSWORD_HASH_COST';

    private const int MINIMUM_COST = 4;

    private const int MAXIMUM_COST = 31;

    private readonly int $cost;

    /**
     * One throw-away hash per cost factor, shared by all instances of the process.
     *
     * @var array<int, string>
     */
    private static array $dummyHashes = [];

    public function __construct(
        private readonly Configuration $configuration,
        ?int $cost = null,
    ) {
        $cost ??= (int) Environment::get(self::COST_ENVIRONMENT_VARIABLE, PASSWORD_BCRYPT_DEFAULT_COST);
        $this->cost = max(self::MINIMUM_COST, min(self::MAXIMUM_COST, $cost));
    }

    /**
     * Hashes a password for storage using bcrypt.
     */
    public function hash(#[SensitiveParameter] string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => $this->cost]);
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
    /**
     * Verifies against a throw-away hash at the configured cost, so a lookup that found
     * no account takes as long as one that found an account with a wrong password.
     * Without this, the missing hash computation makes existing accounts distinguishable
     * by response time (CWE-208). The hash belongs to a discarded random value, so the
     * result is always false; only the elapsed time matters.
     */
    public function burnVerificationTime(#[SensitiveParameter] string $password): bool
    {
        self::$dummyHashes[$this->cost] ??= password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, [
            'cost' => $this->cost,
        ]);

        return password_verify($password, self::$dummyHashes[$this->cost]);
    }

    public function needsRehash(string $storedHash): bool
    {
        if ($this->isLegacyHash($storedHash)) {
            return true;
        }

        return password_needs_rehash($storedHash, PASSWORD_BCRYPT, ['cost' => $this->cost]);
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
