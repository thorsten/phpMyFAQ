<?php

namespace phpMyFAQ\Export\Pdf;

use PHPUnit\Framework\TestCase;

final class MediaHostPolicyTest extends TestCase
{
    public function testIsOriginAllowedAppliesThePortPolicy(): void
    {
        $allowed = ['cdn.test', 'img.test:8080'];

        self::assertTrue(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), $allowed));
        self::assertTrue(MediaHostPolicy::isOriginAllowed(parse_url('https://cdn.test:443/x'), $allowed));
        self::assertTrue(MediaHostPolicy::isOriginAllowed(parse_url('http://sub.cdn.test:80/x'), $allowed));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test:8080/x'), $allowed));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('https://cdn.test:80/x'), $allowed));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('ftp://cdn.test/x'), $allowed));

        // An entry that names a port only matches that port.
        self::assertTrue(MediaHostPolicy::isOriginAllowed(parse_url('http://img.test:8080/x'), $allowed));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://img.test/x'), $allowed));

        // IPv6 literal with a named port.
        self::assertTrue(MediaHostPolicy::isOriginAllowed(
            parse_url('https://[2001:db8::1]:8443/x'),
            ['[2001:db8::1]:8443'],
        ));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(
            parse_url('https://[2001:db8::1]/x'),
            ['[2001:db8::1]:8443'],
        ));
    }

    public function testIsOriginAllowedIgnoresEmptyAndDisabledEntries(): void
    {
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), ['', '0']));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), []));
    }

    public function testNormalizeHostLowercasesAndStripsIpv6Brackets(): void
    {
        self::assertSame('cdn.test', MediaHostPolicy::normalizeHost('CDN.Test'));
        self::assertSame('cdn.test', MediaHostPolicy::normalizeHost('cdn.test.'));
        self::assertSame('::1', MediaHostPolicy::normalizeHost('[::1]'));
        self::assertSame('2001:db8::1', MediaHostPolicy::normalizeHost('[2001:db8::1]'));
    }

    public function testIsOriginAllowedRefusesUrlsWithoutAHost(): void
    {
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('/relative/image.png'), ['cdn.test']));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(['scheme' => 'http', 'path' => '/x'], ['cdn.test']));
    }

    public function testIsOriginAllowedRefusesAHostThatNormalizesToAnEmptyString(): void
    {
        // "." and "[]" are reduced to "" by normalizeHost() and must never match an allowlist entry.
        self::assertFalse(MediaHostPolicy::isOriginAllowed(['scheme' => 'http', 'host' => '.'], ['cdn.test', '.']));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(['scheme' => 'http', 'host' => '[]'], ['cdn.test']));
    }

    public function testIsOriginAllowedIgnoresEntriesWithAnOutOfRangePort(): void
    {
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), ['cdn.test:0']));
        self::assertFalse(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), ['cdn.test:65536']));
        // The out-of-range entry is skipped, a later valid entry still applies.
        self::assertTrue(MediaHostPolicy::isOriginAllowed(parse_url('http://cdn.test/x'), ['cdn.test:0', 'cdn.test']));
    }
}
