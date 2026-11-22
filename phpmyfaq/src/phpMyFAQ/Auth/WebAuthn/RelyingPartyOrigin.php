<?php

/**
 * The web origin a WebAuthn ceremony has to come from.
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

/**
 * The origin the browser reports in the clientDataJSON has to be this installation, not just any
 * site on the same host: the RP ID only pins the host, so a service on another port of that host
 * can obtain assertions for the same RP ID.
 */
final readonly class RelyingPartyOrigin
{
    private string $scheme;

    private ?int $port;

    /**
     * @param string $relyingPartyId the host the credentials are scoped to
     * @param string $installationUrl the configured URL of the installation
     */
    public function __construct(
        private string $relyingPartyId,
        string $installationUrl,
    ) {
        $installation = parse_url($installationUrl);
        $this->scheme = strtolower(is_array($installation) ? $installation['scheme'] ?? '' : '');
        $this->port = self::explicitPort(is_array($installation) ? $installation['port'] ?? null : null, $this->scheme);
    }

    /**
     * The host has to equal the RP ID and the port the one of the installation. The scheme has to
     * be HTTPS or the installation's own: the latter keeps http://localhost setups working, the
     * former an installation behind a TLS-terminating proxy whose configured URL still says http://.
     */
    public function matches(string $clientOrigin): bool
    {
        $origin = parse_url($clientOrigin);
        if (!is_array($origin)) {
            return false;
        }

        $scheme = strtolower($origin['scheme'] ?? '');
        if ($scheme !== 'https' && $scheme !== $this->scheme) {
            return false;
        }

        if (($origin['host'] ?? null) !== $this->relyingPartyId) {
            return false;
        }

        return self::explicitPort($origin['port'] ?? null, $scheme) === $this->port;
    }

    /**
     * A scheme's default port is the same origin as no port at all.
     */
    private static function explicitPort(?int $port, string $scheme): ?int
    {
        $defaultPort = $scheme === 'https' ? 443 : 80;

        return $port === $defaultPort ? null : $port;
    }
}
