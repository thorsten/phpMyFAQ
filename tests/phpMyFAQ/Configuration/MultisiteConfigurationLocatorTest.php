<?php

namespace phpMyFAQ\Configuration;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class MultisiteConfigurationLocatorTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testLocateConfigurationDirectoryReturnsConfigDirIfExists(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('isSecure')->willReturn(false);
        $request->method('getHost')->willReturn('example.com');
        $request->method('getScriptName')->willReturn('/index.php');

        $baseConfigurationDirectory = __DIR__;
        $configDir = $baseConfigurationDirectory . '/example.com';
        if (!is_dir($configDir)) {
            mkdir($configDir);
        }

        $result = MultisiteConfigurationLocator::locateConfigurationDirectory($request, $baseConfigurationDirectory);
        $this->assertEquals($configDir, $result);

        rmdir($configDir);
    }

    /**
     * @throws Exception
     */
    public function testLocateConfigurationDirectoryReturnsNullIfDirDoesNotExist(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('isSecure')->willReturn(false);
        $request->method('getHost')->willReturn('notfound.com');
        $request->method('getScriptName')->willReturn('/index.php');

        $baseConfigurationDirectory = __DIR__;
        $result = MultisiteConfigurationLocator::locateConfigurationDirectory($request, $baseConfigurationDirectory);
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testLocateConfigurationDirectoryReturnsNullIfHostIsEmpty(): void
    {
        $request = $this->createStub(Request::class);
        $request->method('isSecure')->willReturn(false);
        $request->method('getHost')->willReturn('');
        $request->method('getScriptName')->willReturn('/index.php');

        $baseConfigurationDirectory = __DIR__;
        $result = MultisiteConfigurationLocator::locateConfigurationDirectory($request, $baseConfigurationDirectory);
        $this->assertNull($result);
    }

    /**
     * @throws Exception
     */
    public function testLocateConfigurationDirectoryWithSubdomainPattern(): void
    {
        $tenantDir = __DIR__ . '/acme';
        if (!is_dir($tenantDir)) {
            mkdir($tenantDir);
        }

        putenv('PMF_MULTISITE_BASE_DOMAIN=faq.example.com');

        $request = $this->createStub(Request::class);
        $request->method('isSecure')->willReturn(true);
        $request->method('getHost')->willReturn('acme.faq.example.com');
        $request->method('getScriptName')->willReturn('/index.php');

        $result = MultisiteConfigurationLocator::locateConfigurationDirectory($request, __DIR__);
        $this->assertEquals($tenantDir, $result);

        rmdir($tenantDir);
        putenv('PMF_MULTISITE_BASE_DOMAIN');
    }

    public function testExtractTenantFromSubdomainWithValidHost(): void
    {
        putenv('PMF_MULTISITE_BASE_DOMAIN=faq.example.com');

        $this->assertEquals('acme', MultisiteConfigurationLocator::extractTenantFromSubdomain('acme.faq.example.com'));
        $this->assertEquals('test', MultisiteConfigurationLocator::extractTenantFromSubdomain('test.faq.example.com'));

        putenv('PMF_MULTISITE_BASE_DOMAIN');
    }

    public function testExtractTenantFromSubdomainReturnsNullWithoutBaseDomain(): void
    {
        putenv('PMF_MULTISITE_BASE_DOMAIN');

        $this->assertNull(MultisiteConfigurationLocator::extractTenantFromSubdomain('acme.faq.example.com'));
    }

    public function testExtractTenantFromSubdomainReturnsNullForNonMatchingHost(): void
    {
        putenv('PMF_MULTISITE_BASE_DOMAIN=faq.example.com');

        $this->assertNull(MultisiteConfigurationLocator::extractTenantFromSubdomain('other.domain.com'));

        putenv('PMF_MULTISITE_BASE_DOMAIN');
    }

    public function testExtractTenantFromSubdomainRejectsNestedSubdomains(): void
    {
        putenv('PMF_MULTISITE_BASE_DOMAIN=faq.example.com');

        $this->assertNull(MultisiteConfigurationLocator::extractTenantFromSubdomain('deep.nested.faq.example.com'));

        putenv('PMF_MULTISITE_BASE_DOMAIN');
    }

    public function testExtractTenantFromSubdomainRejectsEmptyTenant(): void
    {
        putenv('PMF_MULTISITE_BASE_DOMAIN=faq.example.com');

        $this->assertNull(MultisiteConfigurationLocator::extractTenantFromSubdomain('faq.example.com'));

        putenv('PMF_MULTISITE_BASE_DOMAIN');
    }
}
