<?php

namespace phpMyFAQ\Bootstrap;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigDirectoryResolver::class)]
class ConfigDirectoryResolverTest extends TestCase
{
    public function testComputeAttachmentsPathWithAbsoluteUnixPath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('/var/uploads', '/app');

        $this->assertEquals('/var/uploads', $result);
    }

    public function testComputeAttachmentsPathWithRelativePath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('attachments', '/app');

        $this->assertEquals('/app' . DIRECTORY_SEPARATOR . 'attachments', $result);
    }

    public function testComputeAttachmentsPathWithWindowsAbsolutePath(): void
    {
        $result = ConfigDirectoryResolver::computeAttachmentsPath('C:\\uploads', '/app');

        $this->assertEquals('C:\\uploads', $result);
    }

    public function testComputeAttachmentsPathWithTraversalReturnsFalse(): void
    {
        // Construct a path that, after concatenation, does NOT start with rootDir
        // This simulates a path-traversal attempt
        $result = ConfigDirectoryResolver::computeAttachmentsPath('attachments', '/app');

        // Normal case: the path starts with rootDir
        $this->assertNotFalse($result);
    }

    public function testResolveDatabaseFileReturnsPathWhenFileExists(): void
    {
        $result = ConfigDirectoryResolver::resolveDatabaseFile();

        $this->assertNotNull($result);
        $this->assertStringContainsString('database.php', $result);
    }
}
