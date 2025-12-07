<?php

namespace phpMyFAQ\Instance;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\User;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SetupTest extends TestCase
{
    private Setup $setup;
    private Configuration $configuration;
    private User $user;

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->setup = new Setup();
        $this->configuration = $this->createStub(Configuration::class);
        $this->user = $this->createStub(User::class);
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
}
