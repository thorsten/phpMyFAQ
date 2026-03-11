<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration\Storage\ConfigurationStorageSettings;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationStorageSettings::class)]
class ConfigurationStorageSettingsTest extends TestCase
{
    public function testConstructorAssignsAllValues(): void
    {
        $settings = new ConfigurationStorageSettings(true, 'tcp://redis:6379?database=5', 'custom:config:', 2.5);

        $this->assertTrue($settings->enabled);
        $this->assertSame('tcp://redis:6379?database=5', $settings->redisDsn);
        $this->assertSame('custom:config:', $settings->redisPrefix);
        $this->assertSame(2.5, $settings->connectTimeout);
    }
}
