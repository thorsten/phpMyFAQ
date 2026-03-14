<?php

namespace phpMyFAQ\Filesystem;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class FilesystemTest extends TestCase
{
    private Filesystem $filesystem;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem(PMF_TEST_DIR);
    }

    public function testDeleteDirectory(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/delete-this-directory');

        $actual = $this->filesystem->deleteDirectory(PMF_CONTENT_DIR . '/delete-this-directory');
        $this->assertTrue($actual);
    }

    public function testDeleteDirectoryWithEmptyPath(): void
    {
        $actual = $this->filesystem->deleteDirectory('');
        $this->assertFalse($actual);
    }

    public function testCreateDirectory(): void
    {
        $actual = $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $this->assertTrue($actual);
    }

    public function testCreateDirectoryDuplicateDirectory(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $actual = $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/create-this-directory');
        $this->assertTrue($actual);
    }

    public function testCopy(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/copy-test');
        $actual = $this->filesystem->copy(PMF_TEST_DIR . '/path/foo.bar', PMF_CONTENT_DIR . '/copy-test/foo.bar');
        $this->assertTrue($actual);

        $actual = $this->filesystem->deleteDirectory(PMF_CONTENT_DIR . '/copy-test');
        $this->assertTrue($actual);
    }

    public function testSetPath(): void
    {
        $this->filesystem->setPath(PMF_CONTENT_DIR);
        $this->assertEquals(PMF_CONTENT_DIR, $this->filesystem->getPath());
    }

    public function testMoveDirectory(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/move-directory-test');
        $actual = $this->filesystem->moveDirectory(
            PMF_CONTENT_DIR . '/move-directory-test',
            PMF_CONTENT_DIR . '/move-directory-test-moved',
        );
        $this->assertTrue($actual);
        $actual = $this->filesystem->deleteDirectory(PMF_CONTENT_DIR . '/move-directory-test-moved');
        $this->assertTrue($actual);
    }

    public function testRecursiveCopy(): void
    {
        $testDirectory = PMF_CONTENT_DIR . '/recursive-copy-test';

        $actual = $this->filesystem->recursiveCopy(PMF_TEST_DIR . '/fixtures', $testDirectory);
        $this->assertTrue($actual);

        $actual = is_file(PMF_CONTENT_DIR . '/recursive-copy-test/fixtures/foo.bar');
        $this->assertTrue($actual);

        $actual = $this->filesystem->deleteDirectory($testDirectory);
        $this->assertTrue($actual);
    }

    public function testRecursiveCopyReturnsFalseWhenSourceIsNotDirectory(): void
    {
        $actual = $this->filesystem->recursiveCopy(
            PMF_TEST_DIR . '/path/foo.bar',
            PMF_CONTENT_DIR . '/recursive-copy-missing',
        );

        $this->assertFalse($actual);
    }

    public function testCopyThrowsExceptionWhenSourceIsNotReadable(): void
    {
        $this->filesystem->createDirectory(PMF_CONTENT_DIR . '/copy-test-invalid');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Source not readable or destination directory not writeable.');

        try {
            $this->filesystem->copy(
                PMF_TEST_DIR . '/path/does-not-exist.bar',
                PMF_CONTENT_DIR . '/copy-test-invalid/foo.bar',
            );
        } finally {
            $this->filesystem->deleteDirectory(PMF_CONTENT_DIR . '/copy-test-invalid');
        }
    }

    public function testCopyThrowsExceptionWhenCopyOperationFails(): void
    {
        $targetDirectory = PMF_CONTENT_DIR . '/copy-test-target-directory';
        $this->filesystem->createDirectory($targetDirectory);

        $this->expectException(Exception::class);

        set_error_handler(static fn(): bool => true);

        try {
            $this->filesystem->copy(PMF_TEST_DIR . '/path/foo.bar', $targetDirectory);
        } finally {
            restore_error_handler();
            $this->filesystem->deleteDirectory($targetDirectory);
        }
    }

    public function testDeleteDirectoryReturnsFalseWhenDirectoryCannotBeRead(): void
    {
        $directory = PMF_CONTENT_DIR . '/delete-no-permission-directory';
        $this->filesystem->createDirectory($directory);
        chmod($directory, 0o000);

        set_error_handler(static fn(): bool => true);

        try {
            $this->assertFalse($this->filesystem->deleteDirectory($directory));
        } finally {
            restore_error_handler();
            chmod($directory, 0o700);
            $this->filesystem->deleteDirectory($directory);
        }
    }

    public function testGetRootPath(): void
    {
        $this->assertEquals(PMF_TEST_DIR, $this->filesystem->getRootPath());
    }
}
