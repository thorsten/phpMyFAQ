<?php

declare(strict_types=1);

namespace phpMyFAQ\Http;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(UrlSafetyValidator::class)]
final class UrlSafetyValidatorTest extends TestCase
{
    private const array DNS = [
        'public.example.com' => ['93.184.216.34'],
        'dual.example.com' => ['93.184.216.34', '2606:2800:220:1:248:1893:25c8:1946'],
        'internal.example.com' => ['10.1.2.3'],
        'localhost' => ['127.0.0.1'],
        'mixed.example.com' => ['93.184.216.34', '192.168.0.5'],
        'loopback6.example.com' => ['::1'],
        'metadata.example.com' => ['169.254.169.254'],
        'unresolvable.example.com' => [],
    ];

    private UrlSafetyValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new UrlSafetyValidator(static fn(string $host): array => self::DNS[$host] ?? []);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function outboundUrlProvider(): iterable
    {
        yield 'public https' => ['https://public.example.com/translate', true];
        yield 'public http with port' => ['http://public.example.com:8080/', true];
        yield 'dual stack public' => ['https://dual.example.com', true];
        yield 'public ip literal' => ['https://93.184.216.34/', true];
        yield 'loopback ip' => ['http://127.0.0.1:8080/', false];
        yield 'loopback name' => ['http://localhost/', false];
        yield 'localhost subdomain' => ['http://api.localhost/', false];
        yield 'private range via dns' => ['https://internal.example.com/', false];
        yield 'mixed public and private' => ['https://mixed.example.com/', false];
        yield 'ipv6 loopback via dns' => ['https://loopback6.example.com/', false];
        yield 'ipv6 loopback literal' => ['http://[::1]/', false];
        yield 'link local metadata' => ['http://metadata.example.com/latest/', false];
        yield 'link local literal' => ['http://169.254.169.254/latest/meta-data/', false];
        yield 'carrier grade nat' => ['http://100.64.0.1/', false];
        yield 'unresolvable' => ['https://unresolvable.example.com/', false];
        yield 'ftp scheme' => ['ftp://public.example.com/', false];
        yield 'file scheme' => ['file:///etc/passwd', false];
        yield 'javascript scheme' => ['javascript:alert(1)', false];
        yield 'credentials in url' => ['https://user:pass@public.example.com/', false];
        yield 'not a url' => ['public.example.com', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('outboundUrlProvider')]
    public function testIsSafeOutboundHttpUrl(string $url, bool $expected): void
    {
        self::assertSame($expected, $this->validator->isSafeOutboundHttpUrl($url));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function privateNetworkOutboundUrlProvider(): iterable
    {
        yield 'public https' => ['https://public.example.com/translate', true];
        yield 'private range via dns (container service)' => ['https://internal.example.com/', true];
        yield 'loopback ip (local dev)' => ['http://127.0.0.1:8080/', true];
        yield 'loopback name' => ['http://localhost:8080/realms/faq', true];
        yield 'carrier grade nat' => ['http://100.64.0.1/', true];
        yield 'link local metadata' => ['http://metadata.example.com/latest/', false];
        yield 'link local literal' => ['http://169.254.169.254/latest/meta-data/', false];
        yield 'ipv6 link local literal' => ['http://[fe80::1]/', false];
        yield 'unspecified address' => ['http://0.0.0.0/', false];
        yield 'multicast' => ['http://224.0.0.1/', false];
        yield 'unresolvable' => ['https://unresolvable.example.com/', false];
        yield 'ftp scheme' => ['ftp://internal.example.com/', false];
        yield 'credentials in url' => ['https://user:pass@internal.example.com/', false];
    }

    #[DataProvider('privateNetworkOutboundUrlProvider')]
    public function testIsSafeOutboundHttpUrlAllowingPrivateNetworks(string $url, bool $expected): void
    {
        self::assertSame($expected, $this->validator->isSafeOutboundHttpUrl($url, allowPrivateNetworks: true));
    }

    public function testRedirectUrlsOnlyNeedToBeHttp(): void
    {
        self::assertTrue($this->validator->isHttpUrl('http://localhost/faq/'));
        self::assertTrue($this->validator->isHttpUrl('https://internal.example.com/keycloak/callback'));
        self::assertFalse($this->validator->isHttpUrl('javascript:alert(1)'));
        self::assertFalse($this->validator->isHttpUrl('//evil.example.com'));
        self::assertFalse($this->validator->isHttpUrl('mailto:someone@example.com'));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function redisDsnProvider(): iterable
    {
        yield 'tcp default' => ['tcp://redis:6379?database=1', true];
        yield 'redis scheme' => ['redis://redis:6379/2', true];
        yield 'rediss scheme' => ['rediss://cache.example.com:6380', true];
        yield 'unix socket' => ['unix:///var/run/redis.sock', true];
        yield 'unix socket single slash' => ['unix:/var/run/redis.sock', true];
        yield 'unix socket without path' => ['unix://', false];
        yield 'http is not redis' => ['http://redis:6379', false];
        yield 'missing host' => ['redis://', false];
        yield 'plain host' => ['redis:6379', false];
        yield 'empty' => ['', false];
    }

    #[DataProvider('redisDsnProvider')]
    public function testIsRedisDsn(string $dsn, bool $expected): void
    {
        self::assertSame($expected, $this->validator->isRedisDsn($dsn));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function hostnameListProvider(): iterable
    {
        yield 'single host' => ['www.youtube.com', true];
        yield 'several hosts with spaces' => ['www.youtube.com, player.vimeo.com ,youtu.be', true];
        yield 'empty list' => ['', true];
        yield 'trailing comma' => ['www.youtube.com,', true];
        yield 'scheme is not a host' => ['https://www.youtube.com', false];
        yield 'path is not a host' => ['www.youtube.com/embed', false];
        yield 'port is not a host' => ['www.youtube.com:443', false];
        yield 'wildcard' => ['*.youtube.com', false];
        yield 'injection attempt' => ["www.youtube.com' onload='x", false];
    }

    #[DataProvider('hostnameListProvider')]
    public function testIsHostnameList(string $value, bool $expected): void
    {
        self::assertSame($expected, $this->validator->isHostnameList($value));
    }

    public function testDefaultResolverIsUsedWhenNoneIsInjected(): void
    {
        $validator = new UrlSafetyValidator();

        // IP literals never touch DNS, so the default resolver is safe to construct in tests
        self::assertTrue($validator->isPublicHost('93.184.216.34'));
        self::assertFalse($validator->isPublicHost('127.0.0.1'));
        self::assertFalse($validator->isPublicHost('fe80::1'));
    }
}
