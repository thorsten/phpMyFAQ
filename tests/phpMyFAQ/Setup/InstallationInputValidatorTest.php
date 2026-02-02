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
}
