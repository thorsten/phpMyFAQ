<?php

namespace phpMyFAQ\Setup;

use phpMyFAQ\Core\Exception;
use PHPUnit\Framework\TestCase;

class InstallationInputValidatorTest extends TestCase
{
    private InstallationInputValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new InstallationInputValidator();
    }

    public function testValidateThrowsExceptionForMissingDatabaseType(): void
    {
        // Without POST data and without setup array, dbType will be null
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please select a database type.');
        $this->validator->validate();
    }

    public function testInstallationInputIsReadonly(): void
    {
        $input = new InstallationInput(
            dbSetup: [
                'dbType' => 'pdo_mysql',
                'dbServer' => 'localhost',
                'dbPort' => 3306,
                'dbUser' => 'root',
                'dbPassword' => '',
                'dbDatabaseName' => 'faq',
                'dbPrefix' => '',
            ],
            ldapSetup: [],
            esSetup: [],
            osSetup: [],
            loginName: 'admin',
            password: 'password123',
            language: 'en',
            realname: 'Admin',
            email: 'admin@example.com',
            permLevel: 'basic',
            rootDir: '/tmp',
        );

        $this->assertEquals('admin', $input->getLoginName());
        $this->assertEquals('password123', $input->getPassword());
        $this->assertEquals('en', $input->language);
        $this->assertFalse($input->ldapEnabled);
        $this->assertFalse($input->esEnabled);
        $this->assertFalse($input->osEnabled);
    }

    public function testValidateReturnsInstallationInputForProgrammaticSqliteSetup(): void
    {
        $input = $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'dbPrefix' => 'pmf_',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
            'rootDir' => '/tmp/phpmyfaq-root',
        ]);

        $this->assertSame('sqlite3', $input->dbSetup['dbType']);
        $this->assertSame('/tmp/phpmyfaq-test.sqlite', $input->dbSetup['dbServer']);
        $this->assertSame('admin', $input->getLoginName());
        $this->assertSame('secret123', $input->getPassword());
        $this->assertSame('/tmp/phpmyfaq-root', $input->rootDir);
    }

    public function testValidateThrowsExceptionForInvalidDatabaseType(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Invalid server type "invalid_driver"');

        $this->validator->validate([
            'dbType' => 'invalid_driver',
            'dbServer' => 'localhost',
            'dbPort' => 3306,
            'dbUser' => 'root',
            'dbPassword' => '',
            'dbDatabaseName' => 'phpmyfaq',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingDatabaseServer(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a database server.');

        $this->validator->validate([
            'dbType' => 'mysqli',
            'dbServer' => '',
            'dbPort' => 3306,
            'dbUser' => 'root',
            'dbPassword' => '',
            'dbDatabaseName' => 'phpmyfaq',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingDatabasePort(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a valid database port.');

        $this->validator->validate([
            'dbType' => 'mysqli',
            'dbServer' => 'localhost',
            'dbPort' => null,
            'dbUser' => 'root',
            'dbPassword' => '',
            'dbDatabaseName' => 'phpmyfaq',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingDatabaseUser(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a database username.');

        $this->validator->validate([
            'dbType' => 'mysqli',
            'dbServer' => 'localhost',
            'dbPort' => 3306,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => 'phpmyfaq',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingDatabaseName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a database name.');

        $this->validator->validate([
            'dbType' => 'mysqli',
            'dbServer' => 'localhost',
            'dbPort' => 3306,
            'dbUser' => 'root',
            'dbPassword' => '',
            'dbDatabaseName' => null,
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingLoginName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a login name for your account.');

        $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'password' => 'secret123',
            'password_retyped' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForMissingPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a password for your account.');

        $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'loginname' => 'admin',
        ]);
    }

    public function testValidateThrowsExceptionForMissingRetypedPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Please add a retyped password.');

        $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'loginname' => 'admin',
            'password' => 'secret123',
        ]);
    }

    public function testValidateThrowsExceptionForShortPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Your password and retyped password are too short.');

        $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'loginname' => 'admin',
            'password' => 'short',
            'password_retyped' => 'short',
        ]);
    }

    public function testValidateThrowsExceptionForMismatchedPassword(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Installation Error: Your password and retyped password are not equal.');

        $this->validator->validate([
            'dbType' => 'sqlite3',
            'dbServer' => '/tmp/phpmyfaq-test.sqlite',
            'dbPort' => null,
            'dbUser' => '',
            'dbPassword' => '',
            'dbDatabaseName' => '',
            'loginname' => 'admin',
            'password' => 'secret123',
            'password_retyped' => 'secret124',
        ]);
    }
}
