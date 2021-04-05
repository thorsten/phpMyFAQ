<?php

/**
 * The network class for IPv4 and IPv6 handling.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at http://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Kenneth Shaw <ken@expitrans.com>
 * @author    David Soria Parra <dsp@php.net>
 * @copyright 2011-2021 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2011-02-04
 */

namespace phpMyFAQ;

use InvalidArgumentException;

/**
 * Class Network
 *
 * @package phpMyFAQ
 */
class Network
{
    /**
     * @var Configuration
     */
    private $config;

    /**
     * Constructor.
     *
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * Performs a check if an IPv4 or IPv6 address is banned.
     *
     * @param string $ip IPv4 or IPv6 address
     *
     * @return bool true, if not banned
     */
    public function checkIp(string $ip): bool
    {
        $bannedIps = explode(' ', $this->config->get('security.bannedIPs'));

        foreach ($bannedIps as $ipAddress) {
            if (0 === strlen($ipAddress)) {
                continue;
            }

            if (
                false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
                && false === filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ) {
                // Handle IPv4
                if ($this->checkForAddrMatchIpv4($ip, $ipAddress)) {
                    return false;
                }
            } else {
                // Handle IPv6
                try {
                    return $this->checkForAddrMatchIpv6($ip, $ipAddress);
                } catch (InvalidArgumentException $e) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks for an address match (IPv4 or Network).
     *
     * @param string $ip      IPv4 Address
     * @param string $network Network Address or IPv4 Address
     *
     * @return bool true if IP matched
     */
    public function checkForAddrMatchIpv4($ip, $network): bool
    {
        // See also ip2long PHP online manual: Kenneth Shaw
        // coded a network matching function called net_match.
        // We use here his way of doing bit-by-bit network comparison

        // Start applying the discovering of the network mask
        $ip_arr = explode('/', $network);

        $network_long = ip2long($ip_arr[0]);
        $ip_long = ip2long($ip);

        if (!isset($ip_arr[1])) {
            // $network seems to be a simple ip address, instead of a network address
            $matched = ($network_long == $ip_long);
        } else {
            // $network seems to be a real network address
            $x = ip2long($ip_arr[1]);
            // Evaluate the netmask: <Network Mask> or <CIDR>
            $mask = (long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1])); // @phpstan-ignore-line
            $matched = (($ip_long & $mask) == ($network_long & $mask));
        }

        return $matched;
    }

    /**
     * Checks for an address match (IPv6 or Network).
     *
     * @param string $ip IPv6 Address
     * @param string $network Network Address or IPv6 Address
     * @return bool true if IP matched
     * @throws InvalidArgumentException
     */
    public function checkForAddrMatchIpv6(string $ip, string $network): bool
    {
        if (false === strpos($network, '/')) {
            throw new InvalidArgumentException('Not a valid IPv6 subnet.');
        }

        list($addr, $preflen) = explode('/', $network);
        if (!is_numeric($preflen)) {
            throw new InvalidArgumentException('Not a valid IPv6 preflen.');
        }

        if (!filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidArgumentException('Not a valid IPv6 subnet.');
        }

        $bytes_addr = unpack('n*', inet_pton($addr));
        $bytes_test = unpack('n*', inet_pton($ip));

        for ($i = 1; $i <= ceil($preflen / 16); ++$i) {
            $left = $preflen - 16 * ($i - 1);
            if ($left > 16) {
                $left = 16;
            }
            $mask = ~(0xffff >> $left) & 0xffff;
            if (($bytes_addr[$i] & $mask) != ($bytes_test[$i] & $mask)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Anonymize an IPv4 or IPv6 address.
     *
     * @param string $ipAddress
     * @return string
     */
    public function anonymizeIp(string $ipAddress): string
    {
        return preg_replace(['/\.\d*$/','/[\da-f]*:[\da-f]*$/'], ['.xxx','xxxx:xxxx'], $ipAddress);
    }
}
