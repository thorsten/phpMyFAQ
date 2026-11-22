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
    public function testDefaultHttpClientUsesTimeoutAndRefusesRedirects(): void
    {
        $this->assertSame(10, SendGridProvider::HTTP_CLIENT_OPTIONS['timeout']);
        $this->assertSame(0, SendGridProvider::HTTP_CLIENT_OPTIONS['max_redirects']);

        // The default client must be constructible from the options (no HTTP call is made)
        $provider = new SendGridProvider($this->createStub(Configuration::class));
        $this->assertInstanceOf(SendGridProvider::class, $provider);
    }

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

    public function testSendPostsTextOnlyForPlainMailAndBothPartsForHtmlMail(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(202);

        $contents = new \ArrayObject();
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient
            ->method('request')
            ->willReturnCallback(static function (string $method, string $url, array $options) use (
                $contents,
                $response,
            ): ResponseInterface {
                $contents->append($options['json']['content']);

                return $response;
            });

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturn('secret-key');

        $provider = new SendGridProvider($configuration, $httpClient);
        $headers = ['From' => 'sender@example.com', 'Subject' => 'Test'];
        $provider->send('user@example.com', $headers, 'Plain body');
        $provider->send('user@example.com', $headers, 'Text part', '<p>HTML part</p>');

        $this->assertSame([['type' => 'text/plain', 'value' => 'Plain body']], $contents[0]);
        $this->assertSame(
            [
                ['type' => 'text/plain', 'value' => 'Text part'],
                ['type' => 'text/html', 'value' => '<p>HTML part</p>'],
            ],
            $contents[1],
        );
    }

    public function testSendMapsCopyRecipientsAndReplyTo(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(202);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient
            ->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.sendgrid.com/v3/mail/send',
                $this->callback(static function (array $options): bool {
                    $personalization = $options['json']['personalizations'][0];

                    return (
                        $personalization['to'] === [['email' => 'first@example.com']]
                        && $personalization['cc'] === [['email' => 'copy@example.com']]
                        && $personalization['bcc'] === [['email' => 'blind@example.com']]
                        && $options['json']['reply_to'] === ['email' => 'asker@example.com']
                    );
                }),
            )
            ->willReturn($response);

        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key
            === 'mail.sendgridApiKey'
                ? 'secret-key'
                : null);

        $provider = new SendGridProvider($configuration, $httpClient);

        $provider->send(
            'first@example.com',
            [
                'From' => 'Sender <sender@example.com>',
                'Subject' => 'Test',
                // SendGrid rejects duplicates across to, cc and bcc
                'CC' => '"Copy" <copy@example.com>,<first@example.com>',
                'BCC' => '<blind@example.com>,<copy@example.com>',
                'Reply-To' => '"Asker" <asker@example.com>',
            ],
            'Body',
        );
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
