<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth\WebAuthn;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelyingPartyOrigin::class)]
final class RelyingPartyOriginTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function originProvider(): iterable
    {
        yield 'the installation itself' => ['https://example.com/', 'https://example.com', true];
        yield 'installation in a sub folder' => ['https://example.com/faq/', 'https://example.com', true];
        yield 'default https port spelled out' => ['https://example.com/', 'https://example.com:443', true];
        yield 'scheme in upper case' => ['HTTPS://example.com/', 'https://example.com', true];
        yield 'another port on the same host' => ['https://example.com/', 'https://example.com:8443', false];
        yield 'downgraded to http' => ['https://example.com/', 'http://example.com', false];
        yield 'http on the https port' => ['https://example.com/', 'http://example.com:443', false];
        yield 'another host' => ['https://example.com/', 'https://evil.example', false];
        yield 'a subdomain' => ['https://example.com/', 'https://faq.example.com', false];
        yield 'host as a prefix' => ['https://example.com/', 'https://example.com.evil.example', false];
        yield 'another scheme' => ['https://example.com/', 'android:apk-key-hash:abc', false];
        yield 'no origin' => ['https://example.com/', '', false];
        yield 'no scheme' => ['https://example.com/', 'example.com', false];
        yield 'configured port' => ['https://example.com:8443/', 'https://example.com:8443', true];
        yield 'configured port left out' => ['https://example.com:8443/', 'https://example.com', false];
        yield 'https behind a proxy configured as http' => ['http://example.com/', 'https://example.com', true];
        yield 'http as configured' => ['http://localhost/', 'http://localhost', true];
        yield 'default http port spelled out' => ['http://localhost/', 'http://localhost:80', true];
        yield 'http on another port' => ['http://localhost/', 'http://localhost:8080', false];
        yield 'configured http port' => ['http://localhost:8080/', 'http://localhost:8080', true];
        yield 'unusable installation URL' => ['', 'https://example.com', true];
        yield 'unusable installation URL over http' => ['', 'http://example.com', false];
    }

    #[DataProvider('originProvider')]
    public function testMatchesOnlyTheOriginOfTheInstallation(
        string $installationUrl,
        string $clientOrigin,
        bool $expected,
    ): void {
        $host = parse_url($installationUrl, PHP_URL_HOST);
        $origin = new RelyingPartyOrigin(is_string($host) ? $host : 'example.com', $installationUrl);

        self::assertSame($expected, $origin->matches($clientOrigin));
    }
}
