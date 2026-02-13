<?php

namespace phpMyFAQ\Mail\Provider;

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
}
