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
