<?php

namespace phpMyFAQ\Helper;

use phpMyFAQ\Configuration;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RegistrationHelperTest extends TestCase
{
    private Configuration|MockObject $configuration;
    private RegistrationHelper $registrationHelper;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock(Configuration::class);
        $this->registrationHelper = new RegistrationHelper($this->configuration);
    }

    public function testIsDomainAllowedReturnsTrueWhenNoWhitelist(): void
    {
        $this->configuration->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('');

        $result = $this->registrationHelper->isDomainAllowed('test@example.com');
        $this->assertTrue($result);
    }

    public function testIsDomainAllowedReturnsTrueWhenDomainInWhitelist(): void
    {
        $this->configuration->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('example.com, test.org');

        $result = $this->registrationHelper->isDomainAllowed('test@example.com');
        $this->assertTrue($result);
    }

    public function testIsDomainAllowedReturnsFalseWhenDomainNotInWhitelist(): void
    {
        $this->configuration->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('alloweddomain.com, anotherdomain.org');

        $result = $this->registrationHelper->isDomainAllowed('test@forbiddendomain.com');
        $this->assertFalse($result);
    }

    public function testIsDomainAllowedHandlesNullConfiguration(): void
    {
        $this->configuration->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn(null);

        $result = $this->registrationHelper->isDomainAllowed('test@example.com');
        $this->assertTrue($result);
    }

    public function testIsDomainAllowedHandlesEmptyDomainList(): void
    {
        $this->configuration->method('get')
            ->with('security.domainWhiteListForRegistrations')
            ->willReturn('   ');

        $result = $this->registrationHelper->isDomainAllowed('test@example.com');
        $this->assertTrue($result);
    }
}
