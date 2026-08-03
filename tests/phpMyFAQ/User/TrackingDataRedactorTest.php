<?php

namespace phpMyFAQ\User;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TrackingDataRedactorTest extends TestCase
{
    public function testRedactsPasswordResetSignature(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame(
            'u=1&exp=1785766994&sig=[redacted]',
            $redactor->redactQueryString('u=1&exp=1785766994&sig=308f52b5546849fae74be8ab15164c8c'),
        );
    }

    public function testRedactsEmptyQueryStringToEmptyString(): void
    {
        $this->assertSame('', new TrackingDataRedactor()->redactQueryString(''));
    }

    public function testKeepsNonSensitiveQueryStringUntouched(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame('foo=bar&baz=qux', $redactor->redactQueryString('foo=bar&baz=qux'));
    }

    #[DataProvider('sensitiveParameterProvider')]
    public function testRedactsAllSensitiveParameters(string $queryString, string $expected): void
    {
        $this->assertSame($expected, new TrackingDataRedactor()->redactQueryString($queryString));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function sensitiveParameterProvider(): array
    {
        return [
            'signature' => ['signature=abc', 'signature=[redacted]'],
            'token' => ['token=abc', 'token=[redacted]'],
            'csrf' => ['csrf=abc', 'csrf=[redacted]'],
            'password' => ['password=abc', 'password=[redacted]'],
            'pwd' => ['pwd=abc', 'pwd=[redacted]'],
            'secret' => ['secret=abc', 'secret=[redacted]'],
            'access_token' => ['access_token=abc', 'access_token=[redacted]'],
            'api_key' => ['api_key=abc', 'api_key=[redacted]'],
            'case insensitive' => ['SIG=abc', 'SIG=[redacted]'],
            'mixed with safe' => ['a=1&token=abc&b=2', 'a=1&token=[redacted]&b=2'],
        ];
    }

    public function testIgnoresParametersWithoutValue(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame('sig&foo=bar', $redactor->redactQueryString('sig&foo=bar'));
    }

    public function testRedactsSignatureInRefererUrl(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame(
            'https://example.org/user/reset-password?u=1&exp=99&sig=[redacted]',
            $redactor->redactUrl('https://example.org/user/reset-password?u=1&exp=99&sig=deadbeef'),
        );
    }

    public function testKeepsUrlWithoutQueryStringUntouched(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame('https://example.org/faq', $redactor->redactUrl('https://example.org/faq'));
    }

    public function testPreservesUrlFragmentWhileRedactingQuery(): void
    {
        $redactor = new TrackingDataRedactor();

        $this->assertSame(
            'https://example.org/page?token=[redacted]#section',
            $redactor->redactUrl('https://example.org/page?token=secret#section'),
        );
    }
}
