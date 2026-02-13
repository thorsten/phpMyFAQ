<?php

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MailSettings::class)]
class MailSettingsTest extends TestCase
{
    public function testGetNoReplyEmailReturnsConfiguredNoReplySender(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.noReplySenderAddress' => 'noreply@example.com',
                    'main.administrationMail' => 'admin@example.com',
                    default => null,
                };
            });

        $settings = new MailSettings($configuration);

        $this->assertSame('noreply@example.com', $settings->getNoReplyEmail());
    }

    public function testGetNoReplyEmailFallsBackToAdminMail(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static function (string $key): mixed {
                return match ($key) {
                    'mail.noReplySenderAddress' => '',
                    'main.administrationMail' => 'admin@example.com',
                    default => null,
                };
            });

        $settings = new MailSettings($configuration);

        $this->assertSame('admin@example.com', $settings->getNoReplyEmail());
    }

    public function testGetProviderReturnsConfiguredProvider(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key === 'mail.provider'
            ? 'ses'
            : null);

        $settings = new MailSettings($configuration);

        $this->assertSame('ses', $settings->getProvider());
    }

    public function testGetProviderFallsBackToSmtpForInvalidProvider(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturnCallback(static fn(string $key): mixed => $key === 'mail.provider'
            ? 'invalid'
            : null);

        $settings = new MailSettings($configuration);

        $this->assertSame('smtp', $settings->getProvider());
    }
}
