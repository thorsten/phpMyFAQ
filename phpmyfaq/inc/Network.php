<?php
/**
 * The network class for IPv4 and IPv6 handling
 *
 * PHP Version 5.2
 *
 * The contents of this file are subject to the Mozilla Public License
 * Version 1.1 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.mozilla.org/MPL/
 *
 * Software distributed under the License is distributed on an "AS IS"
 * basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
 * License for the specific language governing rights and limitations
 * under the License.
 *
 * @category  phpMyFAQ
 * @package   PMF_Network
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Kenneth Shaw <ken@expitrans.com>
 * @author    David Soria Parra <dsp@php.net>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-02-04
 */

if (!defined('IS_VALID_PHPMYFAQ')) {
    exit();
}

/**
 * PMF_Report
 *
 * @category  phpMyFAQ
 * @package   PMF_Network
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @author    Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @author    Kenneth Shaw <ken@expitrans.com>
 * @author    David Soria Parra <dsp@php.net>
 * @copyright 2011 phpMyFAQ Team
 * @license   http://www.mozilla.org/MPL/MPL-1.1.html Mozilla Public License Version 1.1
 * @link      http://www.phpmyfaq.de
 * @since     2011-02-04
 */
class PMF_Network
{
    /**
     * Performs a check if an IPv4 or IPv6 address is banned
     *
     * @param string $ip IPv4 or IPv6 address
     *
     * @return boolean true, if not banned
     */
    public function checkIp($ip)
    {
        $bannedList = PMF_Configuration::getInstance()->get('security.bannedIPs');
        $bannedIps  = explode(' ', $bannedList);

        foreach ($bannedIps as $ipAddress) {

            if (0 == strlen($ipAddress)) {
                continue;
            }

            if (false === filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // Handle IPv4
                if ($this->checkForAddrMatchIpv4($ip, $ipAddress)) {
                    return false;
                }
            } else {
                // Handle IPv6
                if ($this->checkForAddrMatchIpv6($ip, $ipAddress)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks for an address match (IPv4 or Network)
     *
     * @param string $ip      IPv4 Address
     * @param string $network Network Address or IPv4 Address
     *
     * @return boolean true if IP matched
     */
    public function checkForAddrMatchIpv4($ip, $network)
    {
        // See also ip2long PHP online manual: Kenneth Shaw
        // coded a network matching function called net_match.
        // We use here his way of doing bit-by-bit network comparison

        // Start applying the discovering of the network mask
        $ip_arr = explode('/', $network);

        $network_long = ip2long($ip_arr[0]);
        $ip_long      = ip2long($ip);

        if (!isset($ip_arr[1])) {
            // $network seems to be a simple ip address, instead of a network address
            $matched = ($network_long == $ip_long);
        } else {
            // $network seems to be a real network address
            $x = ip2long($ip_arr[1]);
            // Evaluate the netmask: <Network Mask> or <CIDR>
            $mask = ( long2ip($x) == $ip_arr[1] ? $x : 0xffffffff << (32 - $ip_arr[1]));
            $matched = ( ($ip_long & $mask) == ($network_long & $mask) );
        }

        return $matched;
    }

    /**
     * Checks for an address match (IPv6 or Network)
     *
     * @param string $ip      IPv6 Address
     * @param string $network Network Address or IPv6 Address
     *
     * @return boolean true if IP matched
     */
    public function checkForAddrMatchIpv6($ip, $network)
    {
        if (false === strpos($network, '/')) {
            throw new InvalidArgumentException("Not a valid IPv6 subnet.");
        }

        list($addr, $preflen) = explode('/', $network);
        if (!is_numeric($preflen)) {
            throw new InvalidArgumentException("Not a valid IPv6 preflen.");
        }

        if (!filter_var($addr, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            throw new InvalidArgumentException("Not a valid IPv6 subnet.");
        }


        $bytes_addr = unpack("n*", inet_pton($addr));
        $bytes_test = unpack("n*", inet_pton($ip));

        for ($i = 1; $i <= ceil($preflen / 16); $i++) {
            $left = $preflen - 16 * ($i-1);
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
}
