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
}
