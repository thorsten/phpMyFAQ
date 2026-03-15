<?php

namespace phpMyFAQ\Mail\Provider;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

#[CoversClass(SendGridProvider::class)]
class SendGridProviderTest extends TestCase
{
    public function testSendThrowsWhenApiKeyMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key
        === 'mail.sendgridApiKey'
            ? ''
            : null);

        $provider = new SendGridProvider($configuration, $this->createStub(HttpClientInterface::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SendGrid API key is not configured.');
        $provider->send('user@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendPostsPayloadAndReturnsRecipientCount(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(202);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.sendgrid.com/v3/mail/send', $this->arrayHasKey('json'))
            ->willReturn($response);

        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.sendgridApiKey' => 'secret-key',
                    default => null,
                };
            });

        $provider = new SendGridProvider($configuration, $httpClient);

        $result = $provider->send(
            'first@example.com,second@example.com',
            ['From' => 'Sender <sender@example.com>', 'Subject' => 'Test'],
            'Body',
        );

        $this->assertSame(2, $result);
    }

    public function testSendThrowsWhenFromHeaderIsInvalid(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key
        === 'mail.sendgridApiKey'
            ? 'secret-key'
            : null);

        $provider = new SendGridProvider($configuration, $this->createStub(HttpClientInterface::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing valid From header for SendGrid provider.');
        $provider->send('user@example.com', ['From' => '', 'Subject' => 'Test'], 'Body');
    }

    public function testSendThrowsWhenNoValidRecipientsExist(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key
        === 'mail.sendgridApiKey'
            ? 'secret-key'
            : null);

        $provider = new SendGridProvider($configuration, $this->createStub(HttpClientInterface::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No valid recipients for SendGrid provider.');
        $provider->send(' , ', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendThrowsWhenApiReturnsNonSuccessfulStatus(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(500);
        $response->method('getContent')->willReturn('provider failure');

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects($this->once())->method('request')->willReturn($response);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key
        === 'mail.sendgridApiKey'
            ? 'secret-key'
            : null);

        $provider = new SendGridProvider($configuration, $httpClient);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SendGrid request failed with status 500: provider failure');
        $provider->send('user@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }
}
