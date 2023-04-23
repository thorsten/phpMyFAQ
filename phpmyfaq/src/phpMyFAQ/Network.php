<?php

/**
 * The network class for IPv4 and IPv6 handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Kenneth Shaw <ken@expitrans.com>
 * @author    David Soria Parra <dsp@php.net>
 * @copyright 2011-2023 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-02-04
 */

namespace phpMyFAQ;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Class Network
 *
 * @package phpMyFAQ
 */
class Network
{
    /**
     * Constructor.
     */
    public function __construct(private readonly Configuration $config)
    {
    }

    /**
     * Performs a check if an IPv4 or IPv6 address is banned.
     *
     * @param string $ip IPv4 or IPv6 address
     *
     * @return bool false, if not banned
     */
    public function isBanned(string $ip): bool
    {
        $bannedIps = explode(' ', (string) $this->config->get('security.bannedIPs'));
        return IpUtils::checkIp($ip, $bannedIps);
    }
}
