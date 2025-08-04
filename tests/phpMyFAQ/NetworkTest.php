<?php

/**
 * Network Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    GitHub Copilot
 * @copyright 2009-2025 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2025-08-04
 */

namespace phpMyFAQ;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class NetworkTest
 */
#[CoversClass(Network::class)]
class NetworkTest extends TestCase
{
    private Configuration $config;
    private Network $network;

    protected function setUp(): void
    {
        $this->config = $this->createMock(Configuration::class);
        $this->network = new Network($this->config);
    }

    public function testConstructorCreatesNetworkInstance(): void
    {
        $network = new Network($this->config);

        $this->assertInstanceOf(Network::class, $network);
    }

    public function testIsBannedReturnsFalseForAllowedIp(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.100 10.0.0.1');

        $result = $this->network->isBanned('192.168.1.1');

        $this->assertFalse($result);
    }

    public function testIsBannedReturnsTrueForBannedIp(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.100 10.0.0.1');

        $result = $this->network->isBanned('192.168.1.100');

        $this->assertTrue($result);
    }

    public function testIsBannedHandlesEmptyBannedList(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('');

        $result = $this->network->isBanned('192.168.1.1');

        $this->assertFalse($result);
    }

    public function testIsBannedHandlesNullBannedList(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn(null);

        $result = $this->network->isBanned('192.168.1.1');

        $this->assertFalse($result);
    }

    public function testIsBannedWithIpv6Address(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('2001:db8::1');

        $result = $this->network->isBanned('2001:db8::1');

        $this->assertTrue($result);
    }

    public function testIsBannedWithIpv6NotBanned(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('2001:db8::1');

        $result = $this->network->isBanned('2001:db8::2');

        $this->assertFalse($result);
    }

    public function testIsBannedWithCidrNotation(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.0/24');

        // IP within the banned subnet
        $result = $this->network->isBanned('192.168.1.50');
        $this->assertTrue($result);

        // IP outside the banned subnet
        $result = $this->network->isBanned('192.168.2.50');
        $this->assertFalse($result);
    }

    public function testIsBannedWithMultipleBannedIps(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.100 10.0.0.1 172.16.0.1');

        $this->assertTrue($this->network->isBanned('192.168.1.100'));
        $this->assertTrue($this->network->isBanned('10.0.0.1'));
        $this->assertTrue($this->network->isBanned('172.16.0.1'));
        $this->assertFalse($this->network->isBanned('8.8.8.8'));
    }

    public function testIsBannedWithInvalidIpAddress(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.100');

        $result = $this->network->isBanned('invalid-ip');

        $this->assertFalse($result);
    }
}
