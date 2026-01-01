<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class HtaccessUpdaterTest extends TestCase
{
    private string $testHtaccessPath;
    private HtaccessUpdater $htaccessUpdater;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testHtaccessPath = PMF_TEST_DIR . '/test.htaccess';
        $this->htaccessUpdater = new HtaccessUpdater();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->testHtaccessPath)) {
            unlink($this->testHtaccessPath);
        }

        $backupFiles = glob($this->testHtaccessPath . '.backup-*');
        foreach ($backupFiles as $backupFile) {
            @unlink($backupFile);
        }
    }

    /**
     * @throws Exception
     */ public function testCreateBackup(): void
    {
        // Create a test .htaccess file
        $originalContent = "RewriteEngine On\nRewriteBase /\n";
        file_put_contents($this->testHtaccessPath, $originalContent);

        $backupPath = $this->htaccessUpdater->createBackup($this->testHtaccessPath);

        $this->assertFileExists($backupPath);
        $this->assertEquals($originalContent, file_get_contents($backupPath));

        // Clean up
        unlink($backupPath);
    }

    public function testCreateBackupFailsForNonExistentFile(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The .htaccess file does not exist at:');

        $this->htaccessUpdater->createBackup('/non/existent/file');
    }

    public function testUpdateRewriteBasePreservesUserContent(): void
    {
        // Create a test .htaccess file with user-generated content
        $originalContent = <<<HTACCESS
        ##
        # phpMyFAQ .htaccess file for Apache 2.x
        #
        DirectoryIndex index.php

        # User added custom directory protection
        AuthType Basic
        AuthName "Protected Area"
        AuthUserFile /path/to/.htpasswd
        Require valid-user

        # Custom redirect
        Redirect 301 /old-page.html /new-page.html

        <IfModule mod_rewrite.c>
            RewriteEngine On
            # the path to your phpMyFAQ installation
            RewriteBase /old/path/
            
            # User added custom rules
            RewriteRule ^api/(.*)$ api/index.php [L,QSA]
            
            # Error pages
            ErrorDocument 404 /index.php?action=404
        </IfModule>

        # User added custom headers
        Header set X-Custom-Header "Custom Value"
        HTACCESS;

        file_put_contents($this->testHtaccessPath, $originalContent);

        // Update RewriteBase
        $result = $this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/new/path/');

        $this->assertTrue($result);

        $updatedContent = file_get_contents($this->testHtaccessPath);

        // Verify RewriteBase was updated
        $this->assertStringContainsString('RewriteBase /new/path/', $updatedContent);
        $this->assertStringNotContainsString('RewriteBase /old/path/', $updatedContent);

        // Verify user content is preserved
        $this->assertStringContainsString('AuthType Basic', $updatedContent);
        $this->assertStringContainsString('AuthName "Protected Area"', $updatedContent);
        $this->assertStringContainsString('Redirect 301 /old-page.html /new-page.html', $updatedContent);
        $this->assertStringContainsString('RewriteRule ^api/(.*)$ api/index.php [L,QSA]', $updatedContent);
        $this->assertStringContainsString('Header set X-Custom-Header "Custom Value"', $updatedContent);
        $this->assertStringContainsString('# User added custom directory protection', $updatedContent);
        $this->assertStringContainsString('# User added custom rules', $updatedContent);
    }

    public function testUpdateRewriteBaseAddsDirectiveIfMissing(): void
    {
        // Create a test .htaccess file without RewriteBase
        $originalContent = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On
            
            # Some custom rules
            RewriteRule ^api/(.*)$ api/index.php [L,QSA]
        </IfModule>
        HTACCESS;

        file_put_contents($this->testHtaccessPath, $originalContent);

        // Update RewriteBase
        $result = $this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/new/path/');

        $this->assertTrue($result);

        $updatedContent = file_get_contents($this->testHtaccessPath);

        // Verify RewriteBase was added
        $this->assertStringContainsString('RewriteBase /new/path/', $updatedContent);
        $this->assertStringContainsString('RewriteRule ^api/(.*)$ api/index.php [L,QSA]', $updatedContent);
    }

    public function testValidateHtaccessStructure(): void
    {
        // Create a valid .htaccess file
        $validContent = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
        </IfModule>
        HTACCESS;

        file_put_contents($this->testHtaccessPath, $validContent);

        $this->assertTrue($this->htaccessUpdater->validateHtaccessStructure($this->testHtaccessPath));
    }

    public function testValidateHtaccessStructureFailsForIncompleteFile(): void
    {
        // Create an incomplete .htaccess file
        $incompleteContent = "# Just a comment\n";
        file_put_contents($this->testHtaccessPath, $incompleteContent);

        $this->assertFalse($this->htaccessUpdater->validateHtaccessStructure($this->testHtaccessPath));
    }

    /**
     * @throws Exception
     */
    public function testUpdateRewriteBaseWithRootPath(): void
    {
        // Create a test .htaccess file
        $originalContent = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /subfolder/
        </IfModule>
        HTACCESS;

        file_put_contents($this->testHtaccessPath, $originalContent);

        // Update RewriteBase to root
        $result = $this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/');

        $this->assertTrue($result);

        $updatedContent = file_get_contents($this->testHtaccessPath);

        // Verify RewriteBase was updated to root
        $this->assertStringContainsString('RewriteBase /', $updatedContent);
        $this->assertStringNotContainsString('RewriteBase /subfolder/', $updatedContent);
    }

    public function testRepeatedCallsAreIdempotentAndDoNotDuplicateOrCreateMultipleBackups(): void
    {
        $content = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On
        </IfModule>
        HTACCESS;
        file_put_contents($this->testHtaccessPath, $content);

        // First run should add one RewriteBase and create exactly one backup
        $this->assertTrue($this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/foo/bar/'));
        $updatedOnce = file_get_contents($this->testHtaccessPath);
        $this->assertEquals(1, substr_count($updatedOnce, 'RewriteBase /foo/bar/'));
        $backupsAfterFirst = glob($this->testHtaccessPath . '.backup-*');
        $this->assertCount(1, $backupsAfterFirst, 'Exactly one backup after first modification');

        // Second run with the same base must not modify or create new backup
        $this->assertTrue($this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/foo/bar/'));
        $updatedTwice = file_get_contents($this->testHtaccessPath);
        $this->assertSame($updatedOnce, $updatedTwice, 'File content must be unchanged on second run');
        $backupsAfterSecond = glob($this->testHtaccessPath . '.backup-*');
        $this->assertCount(1, $backupsAfterSecond, 'No additional backup should be created');
    }

    public function testExistingRewriteBaseWithoutTrailingSlashIsTreatedAsEqual(): void
    {
        $content = <<<HTACCESS
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /foo/bar
        </IfModule>
        HTACCESS;
        file_put_contents($this->testHtaccessPath, $content);

        // Should detect equivalence and not write a backup
        $this->assertTrue($this->htaccessUpdater->updateRewriteBase($this->testHtaccessPath, '/foo/bar/'));
        $backups = glob($this->testHtaccessPath . '.backup-*');
        $this->assertCount(0, $backups, 'No backup should be created when value is equivalent');

        // Content should remain untouched (keep original style without trailing slash)
        $final = file_get_contents($this->testHtaccessPath);
        $this->assertStringContainsString('RewriteBase /foo/bar', $final);
        $this->assertStringNotContainsString('RewriteBase /foo/bar/', $final);
    }
}
