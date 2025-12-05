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
        $this->config = $this->createStub(Configuration::class);
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

        $result = $this->network->isBanned('192.168.1.50');

        $this->assertFalse($result);
    }

    /**
     * Test isBanned returns true for explicitly banned IPv4 address
     */
    public function testIsBannedReturnsTrueForBannedIpv4(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.100 10.0.0.1 172.16.0.1');

        $result = $this->network->isBanned('192.168.1.100');

        $this->assertTrue($result);
    }

    /**
     * Test isBanned with IPv6 addresses
     */
    public function testIsBannedWithIpv6Addresses(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('2001:db8::1 fe80::1 ::1');

        // Test banned IPv6
        $this->assertTrue($this->network->isBanned('2001:db8::1'));
        
        // Test allowed IPv6
        $this->assertFalse($this->network->isBanned('2001:db8::2'));
    }

    /**
     * Test isBanned with CIDR notation for IPv4
     */
    public function testIsBannedWithIpv4CidrNotation(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.0/24 10.0.0.0/8');

        // Test IP within banned CIDR range
        $this->assertTrue($this->network->isBanned('192.168.1.50'));
        $this->assertTrue($this->network->isBanned('192.168.1.255'));
        $this->assertTrue($this->network->isBanned('10.1.2.3'));
        
        // Test IP outside banned CIDR range
        $this->assertFalse($this->network->isBanned('192.168.2.1'));
        $this->assertFalse($this->network->isBanned('172.16.0.1'));
    }

    /**
     * Test isBanned with CIDR notation for IPv6
     */
    public function testIsBannedWithIpv6CidrNotation(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('2001:db8::/32 fe80::/10');

        // Test IPv6 within banned CIDR range
        $this->assertTrue($this->network->isBanned('2001:db8::1'));
        $this->assertTrue($this->network->isBanned('2001:db8:1234::5678'));
        $this->assertTrue($this->network->isBanned('fe80::1'));
        
        // Test IPv6 outside banned CIDR range
        $this->assertFalse($this->network->isBanned('2001:db9::1'));
        $this->assertFalse($this->network->isBanned('2002::1'));
    }

    /**
     * Test isBanned with empty banned IPs list
     */
    public function testIsBannedWithEmptyBannedList(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('');

        $this->assertFalse($this->network->isBanned('192.168.1.1'));
        $this->assertFalse($this->network->isBanned('2001:db8::1'));
    }

    /**
     * Test isBanned with null configuration value
     */
    public function testIsBannedWithNullConfiguration(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn(null);

        $this->assertFalse($this->network->isBanned('192.168.1.1'));
    }

    /**
     * Test isBanned with localhost addresses
     */
    public function testIsBannedWithLocalhostAddresses(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('127.0.0.1 ::1');

        // Test IPv4 localhost
        $this->assertTrue($this->network->isBanned('127.0.0.1'));
        
        // Test IPv6 localhost
        $this->assertTrue($this->network->isBanned('::1'));
        
        // Test other loopback addresses
        $this->assertFalse($this->network->isBanned('127.0.0.2'));
    }

    /**
     * Test isBanned with private IP ranges
     */
    public function testIsBannedWithPrivateIpRanges(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('10.0.0.0/8 172.16.0.0/12 192.168.0.0/16');

        // Test Class A private range
        $this->assertTrue($this->network->isBanned('10.1.2.3'));
        
        // Test Class B private range
        $this->assertTrue($this->network->isBanned('172.16.0.1'));
        $this->assertTrue($this->network->isBanned('172.31.255.255'));
        
        // Test Class C private range
        $this->assertTrue($this->network->isBanned('192.168.1.1'));
        
        // Test public IPs
        $this->assertFalse($this->network->isBanned('8.8.8.8'));
        $this->assertFalse($this->network->isBanned('1.1.1.1'));
    }

    /**
     * Test isBanned with malformed IP addresses
     */
    public function testIsBannedWithMalformedIpAddresses(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.1');

        // Test invalid IPv4
        $this->assertFalse($this->network->isBanned('192.168.1.256'));
        $this->assertFalse($this->network->isBanned('not.an.ip.address'));
        $this->assertFalse($this->network->isBanned('192.168'));
        
        // Test invalid IPv6
        $this->assertFalse($this->network->isBanned('gggg::1'));
        $this->assertFalse($this->network->isBanned('2001:db8:::1'));
    }

    /**
     * Test isBanned with mixed IPv4 and IPv6 banned list
     */
    public function testIsBannedWithMixedIpVersions(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.1.1 2001:db8::1 10.0.0.0/24 fe80::/64');

        // Test IPv4 addresses
        $this->assertTrue($this->network->isBanned('192.168.1.1'));
        $this->assertTrue($this->network->isBanned('10.0.0.50'));
        $this->assertFalse($this->network->isBanned('172.16.0.1'));
        
        // Test IPv6 addresses
        $this->assertTrue($this->network->isBanned('2001:db8::1'));
        $this->assertTrue($this->network->isBanned('fe80::1234'));
        $this->assertFalse($this->network->isBanned('2002::1'));
    }

    /**
     * Test isBanned with edge case IP addresses
     */
    public function testIsBannedWithEdgeCaseIpAddresses(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('0.0.0.0 255.255.255.255 :: ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff');

        // Test edge IPv4 addresses
        $this->assertTrue($this->network->isBanned('0.0.0.0'));
        $this->assertTrue($this->network->isBanned('255.255.255.255'));
        
        // Test edge IPv6 addresses
        $this->assertTrue($this->network->isBanned('::'));
        $this->assertTrue($this->network->isBanned('ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff'));
    }

    /**
     * Test constructor with readonly class behavior
     */
    public function testConstructorReadonlyBehavior(): void
    {
        $network = new Network($this->config);
        
        $this->assertInstanceOf(Network::class, $network);
        
        // Verify that the network instance works correctly
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('127.0.0.1');
            
        $result = $network->isBanned('127.0.0.1');
        $this->assertTrue($result);
    }

    /**
     * Test isBanned with very long banned IPs list
     */
    public function testIsBannedWithLongBannedList(): void
    {
        // Create a long list of banned IPs
        $bannedIps = [];
        for ($i = 1; $i <= 100; $i++) {
            $bannedIps[] = "192.168.1.$i";
        }
        
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn(implode(' ', $bannedIps));

        // Test some IPs from the list
        $this->assertTrue($this->network->isBanned('192.168.1.50'));
        $this->assertTrue($this->network->isBanned('192.168.1.1'));
        $this->assertTrue($this->network->isBanned('192.168.1.100'));
        
        // Test IP not in the list
        $this->assertFalse($this->network->isBanned('192.168.2.1'));
    }

    /**
     * Test isBanned performance with complex CIDR ranges
     */
    public function testIsBannedWithComplexCidrRanges(): void
    {
        $this->config
            ->method('get')
            ->with('security.bannedIPs')
            ->willReturn('192.168.0.0/16 10.0.0.0/8 172.16.0.0/12 169.254.0.0/16');

        // Test multiple IPs efficiently
        $testCases = [
            ['192.168.100.1', true],
            ['10.255.255.255', true],
            ['172.31.0.1', true],
            ['169.254.1.1', true],
            ['8.8.8.8', false],
            ['1.1.1.1', false],
            ['172.15.0.1', false],
            ['172.32.0.1', false]
        ];

        foreach ($testCases as [$ip, $expected]) {
            $this->assertEquals($expected, $this->network->isBanned($ip), "Failed for IP: $ip");
        }
    }
}
