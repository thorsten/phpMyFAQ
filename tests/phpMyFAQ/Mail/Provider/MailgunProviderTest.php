<?php

namespace phpMyFAQ\Mail\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(MailgunProvider::class)]
class MailgunProviderTest extends TestCase
{
    public function testSendThrowsWhenApiKeyMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $key): mixed => match ($key) {
                'mail.mailgunApiKey' => '',
                'mail.mailgunDomain' => 'mg.example.com',
                default => null,
            });

        $provider = new MailgunProvider($configuration, $this->createStub(HttpClientInterface::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mailgun API key is not configured.');
        $provider->send('user@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendThrowsWhenDomainMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $key): mixed => match ($key) {
                'mail.mailgunApiKey' => 'secret-key',
                'mail.mailgunDomain' => '',
                default => null,
            });

        $provider = new MailgunProvider($configuration, $this->createStub(HttpClientInterface::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Mailgun domain is not configured.');
        $provider->send('user@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendPostsPayloadAndReturnsRecipientCount(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.mailgun.net/v3/mg.example.com/messages', $this->arrayHasKey('body'))
            ->willReturn($response);

        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.mailgunApiKey' => 'secret-key',
                    'mail.mailgunDomain' => 'mg.example.com',
                    'mail.mailgunRegion' => 'us',
                    default => null,
                };
            });

        $provider = new MailgunProvider($configuration, $httpClient);

        $result = $provider->send(
            'first@example.com,second@example.com',
            ['From' => 'Sender <sender@example.com>', 'Subject' => 'Test'],
            'Body',
        );

        $this->assertSame(2, $result);
    }

    public function testSendUsesEuEndpointForEuRegion(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.eu.mailgun.net/v3/mg.example.com/messages', $this->anything())
            ->willReturn($response);

        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.mailgunApiKey' => 'secret-key',
                    'mail.mailgunDomain' => 'mg.example.com',
                    'mail.mailgunRegion' => 'eu',
                    default => null,
                };
            });

        $provider = new MailgunProvider($configuration, $httpClient);

        $result = $provider->send('user@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');

        $this->assertSame(1, $result);
    }
}
