<?php

declare(strict_types=1);

namespace phpMyFAQ\Push;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PushEndpointValidator::class)]
final class PushEndpointValidatorTest extends TestCase
{
    /**
     * @var array<string, array<string>>
     */
    private const array DNS = [
        'push.example.test' => ['93.184.216.34'],
        'dual-stack.example.test' => ['93.184.216.34', '2606:4700::6810:1'],
        'loopback.example.test' => ['127.0.0.1'],
        'rfc1918.example.test' => ['93.184.216.34', '10.20.30.40'],
        'cgnat.example.test' => ['100.64.0.1'],
        'link-local.example.test' => ['169.254.169.254'],
        'ipv6-loopback.example.test' => ['::1'],
        'ula.example.test' => ['fd12:3456:789a::1'],
        'ipv6-link-local.example.test' => ['fe80::1'],
        'multicast.example.test' => ['224.0.0.1'],
        'ipv6-multicast.example.test' => ['ff02::1'],
        'mapped.example.test' => ['::ffff:10.0.0.1'],
        'nat64.example.test' => ['64:ff9b::a00:1'],
        'unresolvable.example.test' => [],
    ];

    private function createValidator(): PushEndpointValidator
    {
        return new PushEndpointValidator(static fn(string $host): array => self::DNS[$host] ?? []);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validEndpointProvider(): iterable
    {
        yield 'public host' => ['https://push.example.test/send/abc123'];
        yield 'public host with port and query' => ['https://push.example.test:8443/send?x=1'];
        yield 'dual-stack public host' => ['https://dual-stack.example.test/send'];
        yield 'public ipv4 literal' => ['https://93.184.216.34/send'];
        yield 'public ipv6 literal' => ['https://[2606:4700::6810:1]/send'];
        yield 'upper-case scheme and host' => ['HTTPS://PUSH.EXAMPLE.TEST/send'];
    }

    #[DataProvider('validEndpointProvider')]
    public function testAcceptsPublicHttpsEndpoints(string $endpoint): void
    {
        self::assertTrue($this->createValidator()->isValid($endpoint));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidEndpointProvider(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['not a url'];
        yield 'http scheme' => ['http://push.example.test/send'];
        yield 'ftp scheme' => ['ftp://push.example.test/send'];
        yield 'file scheme' => ['file:///etc/passwd'];
        yield 'scheme only' => ['https://'];
        yield 'credentials in url' => ['https://user:pass@push.example.test/send'];
        yield 'localhost' => ['https://localhost/send'];
        yield 'localhost subdomain' => ['https://foo.localhost/send'];
        yield 'mdns local' => ['https://printer.local/send'];
        yield 'ipv4 loopback literal' => ['https://127.0.0.1/send'];
        yield 'ipv4 zero' => ['https://0.0.0.0/send'];
        yield 'rfc1918 10/8' => ['https://10.1.2.3/send'];
        yield 'rfc1918 172.16/12' => ['https://172.31.255.254/send'];
        yield 'rfc1918 192.168/16' => ['https://192.168.0.1/send'];
        yield 'cgnat literal' => ['https://100.127.1.1/send'];
        yield 'link-local metadata' => ['https://169.254.169.254/latest/meta-data'];
        yield 'multicast literal' => ['https://239.255.255.250/send'];
        yield 'reserved literal' => ['https://240.0.0.1/send'];
        yield 'broadcast literal' => ['https://255.255.255.255/send'];
        yield 'ipv6 loopback literal' => ['https://[::1]/send'];
        yield 'ipv6 unspecified literal' => ['https://[::]/send'];
        yield 'ipv6 ula literal' => ['https://[fc00::1]/send'];
        yield 'ipv6 link-local literal' => ['https://[fe80::1]/send'];
        yield 'ipv6 site-local literal' => ['https://[fec0::1]/send'];
        yield 'ipv6 multicast literal' => ['https://[ff02::1]/send'];
        yield 'ipv6 mapped private literal' => ['https://[::ffff:192.168.1.1]/send'];
        yield 'ipv6 documentation literal' => ['https://[2001:db8::1]/send'];
        yield 'host resolving to loopback' => ['https://loopback.example.test/send'];
        yield 'host resolving partly to rfc1918' => ['https://rfc1918.example.test/send'];
        yield 'host resolving to cgnat' => ['https://cgnat.example.test/send'];
        yield 'host resolving to link-local' => ['https://link-local.example.test/send'];
        yield 'host resolving to ipv6 loopback' => ['https://ipv6-loopback.example.test/send'];
        yield 'host resolving to ula' => ['https://ula.example.test/send'];
        yield 'host resolving to ipv6 link-local' => ['https://ipv6-link-local.example.test/send'];
        yield 'host resolving to multicast' => ['https://multicast.example.test/send'];
        yield 'host resolving to ipv6 multicast' => ['https://ipv6-multicast.example.test/send'];
        yield 'host resolving to mapped ipv4' => ['https://mapped.example.test/send'];
        yield 'host resolving to nat64' => ['https://nat64.example.test/send'];
        yield 'unresolvable host' => ['https://unresolvable.example.test/send'];
    }

    #[DataProvider('invalidEndpointProvider')]
    public function testRejectsNonPublicOrMalformedEndpoints(string $endpoint): void
    {
        self::assertFalse($this->createValidator()->isValid($endpoint));
    }

    public function testResolverIsNotConsultedForIpLiterals(): void
    {
        $validator = new PushEndpointValidator(static function (string $host): array {
            self::fail(sprintf('Resolver must not be called for IP literal "%s".', $host));
        });

        self::assertTrue($validator->isValid('https://93.184.216.34/send'));
        self::assertFalse($validator->isValid('https://10.0.0.1/send'));
    }
}
