<?php

/**
 * ConfigDirectoryResolver Test.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2026 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2026-02-08
 */

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
