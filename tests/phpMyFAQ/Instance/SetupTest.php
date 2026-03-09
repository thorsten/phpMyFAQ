<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class SetupTest extends TestCase
{
    private Setup $setup;
    private Configuration $configuration;
    private User $user;
    private string $tmpRootDir;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setup = new Setup();
        $this->configuration = $this->createStub(Configuration::class);
        $this->user = $this->createStub(User::class);
        $this->tmpRootDir = sys_get_temp_dir() . '/phpmyfaq-setup-test-' . uniqid('', true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_dir($this->tmpRootDir)) {
            $this->removeDirectory($this->tmpRootDir);
        }
    }

    public function testSetRootDir(): void
    {
        $rootDir = '/path/to/root';
        $this->setup->setRootDir($rootDir);

        $reflection = new ReflectionClass($this->setup);
        $property = $reflection->getProperty('rootDir');

        $this->assertSame($rootDir, $property->getValue($this->setup));
    }

    public function testCreateDatabaseFile(): void
    {
        $data = [
            'dbServer' => 'localhost',
            'dbPort' => '3306',
            'dbUser' => 'root',
            'dbPassword' => 'password',
            'dbDatabaseName' => 'phpmyfaq',
            'dbPrefix' => 'pmf_',
            'dbType' => 'mysql',
        ];
        $folder = '/content/core/config';

        $this->expectException(Exception::class);
        $this->setup->createDatabaseFile($data, $folder);
    }

    public function testCreateDatabaseFileWithValidSchemaWritesEscapedConfig(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'dbServer' => "localhost\\db",
            'dbPort' => '3306',
            'dbUser' => "root'user",
            'dbPassword' => 'pass',
            'dbDatabaseName' => 'phpmyfaq',
            'dbPrefix' => 'pmf_',
            'dbType' => 'mysql',
            'dbSchema' => 'tenant_schema1',
        ];

        $result = $this->setup->createDatabaseFile($data);
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/database.php');
        $this->assertNotFalse($content);
        $this->assertStringContainsString("\$DB['schema'] = 'tenant_schema1';", $content);
        $this->assertStringContainsString("\$DB['user'] = 'root\\'user';", $content);
        $this->assertStringContainsString("\$DB['server'] = 'localhost\\\\db';", $content);
    }

    public function testCreateDatabaseFileRejectsInvalidSchema(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'dbServer' => 'localhost',
            'dbPort' => '3306',
            'dbUser' => 'root',
            'dbPassword' => 'password',
            'dbDatabaseName' => 'phpmyfaq',
            'dbPrefix' => 'pmf_',
            'dbType' => 'mysql',
            'dbSchema' => "bad-schema'; die('x'); //",
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid database schema name.');
        $this->setup->createDatabaseFile($data);
    }

    public function testCreateDatabaseFileThrowsWhenFolderNotWritable(): void
    {
        // Create folder and make it non-writable
        $readonlyDir = $this->tmpRootDir . '/readonly';
        mkdir($readonlyDir, 0777, true);
        chmod($readonlyDir, 0444);

        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'dbServer' => 'localhost',
            'dbPort' => '3306',
            'dbUser' => 'root',
            'dbPassword' => 'pass',
            'dbDatabaseName' => 'phpmyfaq',
            'dbPrefix' => '',
            'dbType' => 'mysql',
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('is not writable');

        try {
            $this->setup->createDatabaseFile($data, '/readonly');
        } finally {
            chmod($readonlyDir, 0777);
        }
    }

    public function testCreateDatabaseFileWithEmptySchema(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'dbServer' => 'localhost',
            'dbPort' => '3306',
            'dbUser' => 'root',
            'dbPassword' => 'pass',
            'dbDatabaseName' => 'phpmyfaq',
            'dbPrefix' => 'pmf_',
            'dbType' => 'mysql',
            'dbSchema' => '',
        ];

        $result = $this->setup->createDatabaseFile($data);
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/database.php');
        $this->assertStringContainsString("\$DB['schema'] = '';", $content);
        $this->assertStringContainsString("\$DB['prefix'] = 'pmf_';", $content);
    }

    public function testCreateDatabaseFileWithMissingKeys(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        // All keys missing - should use empty defaults
        $result = $this->setup->createDatabaseFile([]);
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/database.php');
        $this->assertStringContainsString("\$DB['server'] = '';", $content);
        $this->assertStringContainsString("\$DB['type'] = '';", $content);
    }

    public function testCheckDirsWithExistingWritableDir(): void
    {
        mkdir($this->tmpRootDir . '/setup', 0777, true);
        file_put_contents($this->tmpRootDir . '/setup/index.html', '<html></html>');
        mkdir($this->tmpRootDir . '/existing-dir', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $result = $this->setup->checkDirs(['/existing-dir']);
        $this->assertEmpty($result);
    }

    public function testCheckDirsCreatesNewDir(): void
    {
        @mkdir($this->tmpRootDir . '/setup', 0777, true);
        file_put_contents($this->tmpRootDir . '/setup/index.html', '<html></html>');
        $this->setup->setRootDir($this->tmpRootDir);

        $result = $this->setup->checkDirs(['/new-dir']);
        $this->assertEmpty($result);
        $this->assertDirectoryExists($this->tmpRootDir . '/new-dir');
    }

    public function testCheckDirsReportsNonWritableDir(): void
    {
        mkdir($this->tmpRootDir . '/nowrite', 0777, true);
        chmod($this->tmpRootDir . '/nowrite', 0444);
        $this->setup->setRootDir($this->tmpRootDir);

        $result = $this->setup->checkDirs(['/nowrite']);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString('not writable', $result[0]);

        chmod($this->tmpRootDir . '/nowrite', 0777);
    }

    public function testCreateLdapFile(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'ldapServer' => 'ldap.example.com',
            'ldapPort' => '389',
            'ldapUser' => 'cn=admin,dc=example,dc=com',
            'ldapPassword' => 'secret',
            'ldapBase' => 'dc=example,dc=com',
        ];

        $result = $this->setup->createLdapFile($data, '/content/core');
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/ldap.php');
        $this->assertStringContainsString("\$PMF_LDAP['ldap_server'] = 'ldap.example.com'", $content);
        $this->assertStringContainsString("\$PMF_LDAP['ldap_port'] = '389'", $content);
        $this->assertStringContainsString("\$PMF_LDAP['ldap_user'] = 'cn=admin,dc=example,dc=com'", $content);
        $this->assertStringContainsString("\$PMF_LDAP['ldap_password'] = 'secret'", $content);
        $this->assertStringContainsString("\$PMF_LDAP['ldap_base'] = 'dc=example,dc=com'", $content);
    }

    public function testCreateElasticsearchFile(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'hosts' => ['localhost:9200', 'localhost:9201'],
            'index' => 'phpmyfaq',
        ];

        $result = $this->setup->createElasticsearchFile($data, '/content/core');
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/elasticsearch.php');
        $this->assertStringContainsString("\$PMF_ES['hosts']", $content);
        $this->assertStringContainsString('localhost:9200', $content);
        $this->assertStringContainsString('localhost:9201', $content);
        $this->assertStringContainsString("\$PMF_ES['index'] = 'phpmyfaq'", $content);
    }

    public function testCreateOpenSearchFile(): void
    {
        mkdir($this->tmpRootDir . '/content/core/config/config', 0777, true);
        $this->setup->setRootDir($this->tmpRootDir);

        $data = [
            'hosts' => ['localhost:9200'],
            'index' => 'phpmyfaq_os',
        ];

        $result = $this->setup->createOpenSearchFile($data, '/content/core');
        $this->assertIsInt($result);

        $content = file_get_contents($this->tmpRootDir . '/content/core/config/opensearch.php');
        $this->assertStringContainsString("\$PMF_OS['hosts']", $content);
        $this->assertStringContainsString('localhost:9200', $content);
        $this->assertStringContainsString("\$PMF_OS['index'] = 'phpmyfaq_os'", $content);
    }

    private function removeDirectory(string $directory): void
    {
        $entries = scandir($directory);
        if ($entries === false) {
            return;
        }

        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
                continue;
            }

            unlink($path);
        }

        rmdir($directory);
    }
}
