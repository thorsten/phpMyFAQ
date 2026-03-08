<?php

declare(strict_types=1);

namespace phpMyFAQ\Configuration;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LdapSettings::class)]
final class LdapSettingsTest extends TestCase
{
    public function testIsActiveReturnsFalseForStringFalse(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('ldap.ldapSupport')->willReturn('false');

        $settings = new LdapSettings($configuration);

        self::assertFalse($settings->isActive());
    }

    public function testIsActiveReturnsTrueForStringTrue(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('get')->with('ldap.ldapSupport')->willReturn('true');

        $settings = new LdapSettings($configuration);

        self::assertTrue($settings->isActive());
    }
}
