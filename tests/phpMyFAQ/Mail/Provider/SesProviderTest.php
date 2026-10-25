<?php

namespace phpMyFAQ\Mail\Provider;

use Aws\MockHandler;
use Aws\Result;
use Aws\Ses\SesClient;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SesProvider::class)]
class SesProviderTest extends TestCase
{
    public function testSendThrowsWhenNoFromHeaderExists(): void
    {
        $provider = new SesProvider($this->createStub(Configuration::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Missing valid From header for SES provider.');
        $provider->send('user@example.com', ['Subject' => 'Test'], 'Body');
    }

    public function testSendThrowsWhenCredentialsAreMissing(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.sesAccessKeyId' => '',
                    'mail.sesSecretAccessKey' => '',
                    'mail.sesRegion' => 'us-east-1',
                    default => null,
                };
            });

        $provider = new SesProvider($configuration);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('SES credentials are not configured.');
        $provider->send('first@example.com', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendThrowsWhenNoRecipientIsValid(): void
    {
        $provider = new SesProvider($this->createStub(Configuration::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('No valid recipients for SES provider.');
        $provider->send('', ['From' => 'sender@example.com', 'Subject' => 'Test'], 'Body');
    }

    public function testSendUsesInjectedClientAndCountsUniqueRecipients(): void
    {
        $handler = new MockHandler([new Result(['MessageId' => 'abc'])]);
        $credential = 'test-credential';
        $sesClient = new SesClient([
            'version' => 'latest',
            'region' => 'eu-central-1',
            'credentials' => ['key' => $credential, 'secret' => $credential],
            'handler' => $handler,
        ]);

        $provider = new SesProvider($this->createStub(Configuration::class), $sesClient);

        $sent = $provider->send(
            'First <first@example.com>, second@example.com, first@example.com',
            ['From' => 'Sender <sender@example.com>', 'Subject' => 'Test'],
            'Body',
        );

        $this->assertSame(2, $sent);
        $command = $handler->getLastCommand();
        $this->assertSame('SendEmail', $command->getName());
        $this->assertSame('sender@example.com', $command['Source']);
        $this->assertSame(['first@example.com', 'second@example.com'], $command['Destination']['ToAddresses']);
    }

    public function testGetSesClientBuildsClientFromConfiguredCredentials(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.sesAccessKeyId' => 'access-key',
                    'mail.sesSecretAccessKey' => 'secret-key',
                    'mail.sesRegion' => 'eu-west-1',
                    default => null,
                };
            });

        $provider = new SesProvider($configuration);
        $method = new \ReflectionMethod(SesProvider::class, 'getSesClient');

        $sesClient = $method->invoke($provider);

        $this->assertInstanceOf(SesClient::class, $sesClient);
        $this->assertSame('eu-west-1', $sesClient->getRegion());
        $this->assertSame($sesClient, $method->invoke($provider));
    }
}
