<?php

namespace phpMyFAQ\Export\Pdf;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;

final class StreamHttpRequesterTest extends TestCase
{
    public function testRequestReturnsZeroStatusAndFalseBodyWhenTheHostCannotBeResolved(): void
    {
        // ".invalid" is reserved by RFC 2606 to never resolve, so this deterministically
        // exercises the failure path without depending on real network availability.
        set_error_handler(static fn(): bool => true);
        try {
            [$statusCode, $headers, $body] = new StreamHttpRequester()->request('https://example.invalid/image.png');
        } finally {
            restore_error_handler();
        }

        self::assertSame(0, $statusCode);
        self::assertIsArray($headers);
        self::assertFalse($body);
    }

    public function testParseHttpStatusCodeExtractsTheCodeFromAValidStatusLine(): void
    {
        $requester = new StreamHttpRequester();
        $method = new ReflectionMethod($requester, 'parseHttpStatusCode');

        self::assertSame(200, $method->invoke($requester, ['HTTP/1.1 200 OK']));
        self::assertSame(302, $method->invoke($requester, ['HTTP/1.1 302 Found', 'Location: https://example.test/']));
    }

    public function testParseHttpStatusCodeReturnsZeroForMissingOrMalformedStatusLine(): void
    {
        $requester = new StreamHttpRequester();
        $method = new ReflectionMethod($requester, 'parseHttpStatusCode');

        self::assertSame(0, $method->invoke($requester, []));
        self::assertSame(0, $method->invoke($requester, ['not a status line']));
    }

    public function testRequestRefusesHostsThatResolveToNonRoutableAddressesWithoutConnecting(): void
    {
        // Loopback and link-local destinations (the latter covers cloud metadata endpoints) must
        // never be contacted, so request() returns the failure tuple before opening any socket.
        $requester = new StreamHttpRequester();

        foreach ([
            'http://localhost/x.png',
            'http://127.0.0.1/x.png',
            'http://169.254.169.254/latest/meta-data',
            'http://[::1]/x.png',
        ] as $url) {
            set_error_handler(static fn(): bool => true);
            try {
                [$statusCode, $headers, $body] = $requester->request($url);
            } finally {
                restore_error_handler();
            }

            self::assertSame(0, $statusCode, $url . ' must not be contacted');
            self::assertIsArray($headers);
            self::assertFalse($body, $url . ' must not be contacted');
        }
    }

    public function testResolveSafeAddressRefusesNonRoutableDestinations(): void
    {
        $requester = new StreamHttpRequester();
        $method = new ReflectionMethod($requester, 'resolveSafeAddress');

        foreach ([
            'localhost',
            '127.0.0.1',
            '127.255.255.254',
            '0.0.0.0',
            '169.254.169.254',
            '224.0.0.1',
            '255.255.255.255',
            '240.0.0.1',
            '::1',
            '[::1]',
            '::',
            '::ffff:127.0.0.1',
            '::ffff:169.254.169.254',
            '::127.0.0.1',
            'fe80::1',
            'ff02::1',
            '',
        ] as $host) {
            self::assertNull($method->invoke($requester, $host), $host . ' must not be contacted');
        }
    }

    public function testResolveSafeAddressKeepsRoutableAndPrivateDestinations(): void
    {
        $requester = new StreamHttpRequester();
        $method = new ReflectionMethod($requester, 'resolveSafeAddress');

        // Intranet image hosts are a legitimate configuration, so RFC 1918 / ULA stay allowed.
        foreach (['203.0.113.10', '10.0.0.5', '172.16.0.5', '192.168.1.5', '2001:db8::1', 'fd00::1'] as $host) {
            self::assertSame($host, $method->invoke($requester, $host));
        }

        self::assertSame('203.0.113.10', $method->invoke($requester, '[203.0.113.10]'));
    }

    public function testResolvePinnedTargetConnectsToTheLiteralAddressAndKeepsTheHostName(): void
    {
        $requester = new StreamHttpRequester();
        $method = new ReflectionMethod($requester, 'resolvePinnedTarget');

        // A routable literal is pinned verbatim, the Host header carries host and port, and the
        // TLS peer name is the host (not the address), so certificate verification still works.
        [$connectUrl, $hostHeader, $peerName] = $method->invoke($requester, 'http://203.0.113.10:8080/a/b.png?x=1');
        self::assertSame('http://203.0.113.10:8080/a/b.png?x=1', $connectUrl);
        self::assertSame('203.0.113.10:8080', $hostHeader);
        self::assertSame('203.0.113.10', $peerName);

        // An IPv6 literal is bracketed in the connect URL.
        [$connectUrl] = $method->invoke($requester, 'https://[2001:db8::1]/c.png');
        self::assertSame('https://[2001:db8::1]/c.png', $connectUrl);

        self::assertNull($method->invoke($requester, 'http://127.0.0.1/x.png'));
        self::assertNull($method->invoke($requester, 'ftp://203.0.113.10/x.png'));
    }
}
