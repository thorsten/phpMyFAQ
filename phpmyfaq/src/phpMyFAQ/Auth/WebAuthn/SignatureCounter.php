<?php

/**
 * The signature counter an authenticator reports with every WebAuthn ceremony.
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
 * @since     2026-11-22
 */

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use phpMyFAQ\Core\Exception;

final readonly class SignatureCounter
{
    /** The counter follows the RP ID hash (32 bytes) and the flags (1 byte). */
    private const int OFFSET = 33;

    private const int LENGTH = 4;

    private function __construct(
        public int $value,
    ) {
    }

    /**
     * @throws Exception
     */
    public static function fromAuthenticatorData(string $authenticatorData): self
    {
        $bytes = substr($authenticatorData, self::OFFSET, self::LENGTH);
        if (strlen($bytes) !== self::LENGTH) {
            throw new Exception('Authenticator data is too short to carry a signature counter');
        }

        // Big-endian unsigned 32-bit integer.
        return new self((ord($bytes[0]) << 24) | (ord($bytes[1]) << 16) | (ord($bytes[2]) << 8) | ord($bytes[3]));
    }

    /**
     * Cloned-authenticator detection: unless the authenticator does not count at all (the stored
     * and the received counter are both zero, as with synced passkeys), every assertion has to
     * carry a higher counter than the last one seen. A counter that stands still or goes back
     * means two copies of the credential's private key are in use.
     */
    public function follows(int $storedCounter): bool
    {
        if ($storedCounter === 0 && $this->value === 0) {
            return true;
        }

        return $this->value > $storedCounter;
    }
}
