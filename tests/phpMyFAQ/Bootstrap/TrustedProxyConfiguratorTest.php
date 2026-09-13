<?php

declare(strict_types=1);

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(TrustedProxyConfigurator::class)]
final class TrustedProxyConfiguratorTest extends TestCase
{
    /** @var string[] */
    private array $previousProxies = [];
    private int $previousHeaderSet = 0;
    private mixed $previousEnvValue = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousProxies = Request::getTrustedProxies();
        $this->previousHeaderSet = Request::getTrustedHeaderSet();
        $this->previousEnvValue = $_ENV[TrustedProxyConfigurator::ENVIRONMENT_VARIABLE] ?? null;
        Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
    }

    protected function tearDown(): void
    {
        Request::setTrustedProxies($this->previousProxies, $this->previousHeaderSet ?: Request::HEADER_X_FORWARDED_FOR);
        if ($this->previousEnvValue === null) {
            unset($_ENV[TrustedProxyConfigurator::ENVIRONMENT_VARIABLE]);
        } else {
            $_ENV[TrustedProxyConfigurator::ENVIRONMENT_VARIABLE] = $this->previousEnvValue;
        }

        parent::tearDown();
    }

    public function testConfigureTrustsListedProxiesForForwardedHeaders(): void
    {
        TrustedProxyConfigurator::configure('10.0.0.0/8, 192.168.1.5');

        self::assertSame(['10.0.0.0/8', '192.168.1.5'], Request::getTrustedProxies());
        self::assertSame(TrustedProxyConfigurator::TRUSTED_HEADER_SET, Request::getTrustedHeaderSet());
    }

    public function testRemoteAddrKeywordTrustsTheImmediatePeer(): void
    {
        $previousRemoteAddr = $_SERVER['REMOTE_ADDR'] ?? null;
        $_SERVER['REMOTE_ADDR'] = '203.0.113.9';

        try {
            TrustedProxyConfigurator::configure('REMOTE_ADDR');

            // Symfony resolves the keyword to the current peer address
            self::assertSame(['203.0.113.9'], Request::getTrustedProxies());
        } finally {
            if ($previousRemoteAddr === null) {
                unset($_SERVER['REMOTE_ADDR']);
            } else {
                $_SERVER['REMOTE_ADDR'] = $previousRemoteAddr;
            }
        }
    }

    public function testConfigureLeavesRequestUntouchedForEmptyValue(): void
    {
        TrustedProxyConfigurator::configure('   ');

        self::assertSame([], Request::getTrustedProxies());
    }

    public function testForwardedHeadersAreIgnoredWithoutTrustedProxies(): void
    {
        $request = Request::create('/', server: [
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]);

        self::assertSame('203.0.113.9', $request->getClientIp());
        self::assertFalse($request->isSecure());
    }

    public function testForwardedHeadersAreHonouredFromTrustedProxy(): void
    {
        TrustedProxyConfigurator::configure('203.0.113.0/24');

        $request = Request::create('/', server: [
            'REMOTE_ADDR' => '203.0.113.9',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.7',
            'HTTP_X_FORWARDED_PROTO' => 'https',
            'HTTP_X_FORWARDED_HOST' => 'faq.example.com',
        ]);

        self::assertSame('198.51.100.7', $request->getClientIp());
        self::assertTrue($request->isSecure());
        self::assertSame('faq.example.com', $request->getHost());
    }

    public function testConfigureFromEnvironmentReadsDotEnvValue(): void
    {
        $_ENV[TrustedProxyConfigurator::ENVIRONMENT_VARIABLE] = '10.9.8.7';

        TrustedProxyConfigurator::configureFromEnvironment();

        self::assertSame(['10.9.8.7'], Request::getTrustedProxies());
    }

    /**
     * @param string[] $expected
     */
    #[DataProvider('parseProvider')]
    public function testParseDropsInvalidEntries(string $value, array $expected): void
    {
        self::assertSame($expected, TrustedProxyConfigurator::parse($value));
    }

    /**
     * @return iterable<string, array{string, string[]}>
     */
    public static function parseProvider(): iterable
    {
        yield 'ipv4 and cidr' => ['127.0.0.1,10.0.0.0/8', ['127.0.0.1', '10.0.0.0/8']];
        yield 'ipv6' => ['::1, fd00::/8', ['::1', 'fd00::/8']];
        yield 'remote addr keyword' => ['REMOTE_ADDR', ['REMOTE_ADDR']];
        yield 'hostnames are rejected' => ['proxy.example.com,10.1.1.1', ['10.1.1.1']];
        yield 'invalid prefix is rejected' => ['10.0.0.0/33,10.0.0.0/abc', []];
        yield 'garbage is rejected' => ['<script>,;', []];
        yield 'empty' => ['', []];
    }
}
