<?php

/**
 * Holds the Content-Security-Policy nonce of the current request
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
 * @since     2026-11-08
 */

declare(strict_types=1);

namespace phpMyFAQ\Http;

use Random\RandomException;

/**
 * The templates render the nonce into every inline <script>, the
 * SecurityHeadersListener renders it into the script-src directive of the
 * Content-Security-Policy header. Both happen in the same request but in
 * different objects - the header is written after the template was rendered -
 * so the value lives in a per-process static instead of being passed around.
 *
 * A nonce must never be reused across requests: an attacker who reads one
 * from a page could otherwise whitelist their own injected script on a later
 * page view. Every PHP process serves exactly one request, except in
 * FrankenPHP worker mode, where WorkerState::reset() calls reset() here.
 */
final class ContentSecurityPolicyNonce
{
    /**
     * 16 random bytes, the length recommended by the CSP Level 3
     * specification, base64-encoded as required by its nonce-source grammar.
     */
    private const int NONCE_BYTES = 16;

    private static ?string $nonce = null;

    /**
     * Returns the nonce of the current request, generating it on first use.
     *
     * @throws RandomException if no cryptographically secure source of randomness is available
     */
    public static function get(): string
    {
        return self::$nonce ??= base64_encode(random_bytes(self::NONCE_BYTES));
    }

    /**
     * Drops the nonce so the next request gets a fresh one.
     */
    public static function reset(): void
    {
        self::$nonce = null;
    }
}
