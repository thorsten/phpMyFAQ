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
}
