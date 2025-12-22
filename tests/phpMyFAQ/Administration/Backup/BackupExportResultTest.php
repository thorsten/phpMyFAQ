<?php

namespace phpMyFAQ\Administration\Backup;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/**
 * Class BackupExportResultTest
 *
 * @package phpMyFAQ\Administration\Backup
 */
#[AllowMockObjectsWithoutExpectations]
class BackupExportResultTest extends TestCase
{
    public function testConstructorWithValidParameters(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = 'SELECT * FROM faqconfig;';

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($fileName, $result->fileName);
        $this->assertEquals($content, $result->content);
    }

    public function testConstructorWithEmptyContent(): void
    {
        $fileName = 'phpmyfaq-logs.2025-12-22-10-30-45.sql';
        $content = '';

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($fileName, $result->fileName);
        $this->assertEquals('', $result->content);
    }

    public function testConstructorWithLargeContent(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = str_repeat('INSERT INTO faqdata VALUES (1, "test");\n', 10000);

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($fileName, $result->fileName);
        $this->assertEquals($content, $result->content);
        $this->assertGreaterThan(100000, strlen($result->content));
    }

    public function testConstructorWithMultilineContent(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = "-- pmf4.0: faqconfig\n" .
                   "-- DO NOT REMOVE THE FIRST LINE!\n" .
                   "INSERT INTO faqconfig VALUES (1, 'test');";

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($fileName, $result->fileName);
        $this->assertEquals($content, $result->content);
        $this->assertStringContainsString('pmf4.0', $result->content);
    }

    public function testConstructorWithSpecialCharactersInContent(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = "INSERT INTO faqdata VALUES (1, 'Test with \'quotes\' and \"double quotes\"');";

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($content, $result->content);
    }

    public function testConstructorWithDifferentBackupTypes(): void
    {
        $dataBackup = new BackupExportResult(
            'phpmyfaq-data.2025-12-22-10-30-45.sql',
            'data content'
        );

        $logsBackup = new BackupExportResult(
            'phpmyfaq-logs.2025-12-22-10-30-46.sql',
            'logs content'
        );

        $this->assertStringContainsString('data', $dataBackup->fileName);
        $this->assertStringContainsString('logs', $logsBackup->fileName);
        $this->assertNotEquals($dataBackup->fileName, $logsBackup->fileName);
    }

    public function testConstructorWithTablePrefix(): void
    {
        $fileName = 'prefix.phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = 'INSERT INTO prefix_faqconfig VALUES (1);';

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($fileName, $result->fileName);
        $this->assertEquals($content, $result->content);
        $this->assertStringContainsString('prefix', $result->fileName);
    }

    public function testReadonlyProperties(): void
    {
        $result = new BackupExportResult('test.sql', 'content');

        // Verify properties are accessible
        $this->assertIsString($result->fileName);
        $this->assertIsString($result->content);
    }

    public function testConstructorWithUnicodeContent(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = "INSERT INTO faqdata VALUES (1, 'Tëst with ünïcödé çhäracters 日本語');";

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($content, $result->content);
        $this->assertStringContainsString('ünïcödé', $result->content);
    }

    public function testConstructorWithBackslashesInContent(): void
    {
        $fileName = 'phpmyfaq-data.2025-12-22-10-30-45.sql';
        $content = "INSERT INTO faqdata VALUES (1, 'Path: C:\\\\Users\\\\test');";

        $result = new BackupExportResult($fileName, $content);

        $this->assertEquals($content, $result->content);
        $this->assertStringContainsString('\\\\', $result->content);
    }
}
