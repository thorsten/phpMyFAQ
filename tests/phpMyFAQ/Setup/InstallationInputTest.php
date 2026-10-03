<?php

declare(strict_types=1);

namespace phpMyFAQ\Setup;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use SensitiveParameter;

#[CoversClass(InstallationInput::class)]
final class InstallationInputTest extends TestCase
{
    private function createInput(bool $ldapEnabled = false, bool $esEnabled = false, bool $osEnabled = false): InstallationInput
    {
        return new InstallationInput(
            dbSetup: ['dbType' => 'sqlite3', 'dbServer' => '/tmp/faq.db'],
            ldapSetup: ['ldapServer' => 'ldap.example.org'],
            esSetup: ['hosts' => ['es:9200']],
            osSetup: ['hosts' => ['os:9200']],
            loginName: 'admin',
            password: 's3cret',
            language: 'de',
            realname: 'Admin User',
            email: 'admin@example.org',
            permLevel: 'medium',
            rootDir: '/var/www/phpmyfaq',
            ldapEnabled: $ldapEnabled,
            esEnabled: $esEnabled,
            osEnabled: $osEnabled,
        );
    }

    public function testPublicPropertiesExposeSetupData(): void
    {
        $input = $this->createInput();

        $this->assertSame(['dbType' => 'sqlite3', 'dbServer' => '/tmp/faq.db'], $input->dbSetup);
        $this->assertSame(['ldapServer' => 'ldap.example.org'], $input->ldapSetup);
        $this->assertSame(['hosts' => ['es:9200']], $input->esSetup);
        $this->assertSame(['hosts' => ['os:9200']], $input->osSetup);
        $this->assertSame('de', $input->language);
        $this->assertSame('Admin User', $input->realname);
        $this->assertSame('medium', $input->permLevel);
        $this->assertSame('/var/www/phpmyfaq', $input->rootDir);
    }

    public function testCredentialsAreOnlyReachableThroughGetters(): void
    {
        $input = $this->createInput();

        $this->assertSame('admin', $input->getLoginName());
        $this->assertSame('s3cret', $input->getPassword());
        $this->assertSame('admin@example.org', $input->getEmail());

        $reflection = new ReflectionClass($input);
        $this->assertTrue($reflection->getProperty('loginName')->isPrivate());
        $this->assertTrue($reflection->getProperty('password')->isPrivate());
        $this->assertTrue($reflection->getProperty('email')->isPrivate());
    }

    public function testPasswordParameterIsMarkedSensitive(): void
    {
        $constructor = new ReflectionClass(InstallationInput::class)->getConstructor();
        $this->assertNotNull($constructor);

        $passwordParameter = null;
        foreach ($constructor->getParameters() as $parameter) {
            if ($parameter->getName() === 'password') {
                $passwordParameter = $parameter;
            }
        }

        $this->assertNotNull($passwordParameter);
        $this->assertNotEmpty($passwordParameter->getAttributes(SensitiveParameter::class));
    }

    public function testOptionalIntegrationsAreDisabledByDefault(): void
    {
        $input = $this->createInput();

        $this->assertFalse($input->ldapEnabled);
        $this->assertFalse($input->esEnabled);
        $this->assertFalse($input->osEnabled);
    }

    public function testOptionalIntegrationsCanBeEnabled(): void
    {
        $input = $this->createInput(ldapEnabled: true, esEnabled: true, osEnabled: true);

        $this->assertTrue($input->ldapEnabled);
        $this->assertTrue($input->esEnabled);
        $this->assertTrue($input->osEnabled);
    }

    public function testInputIsImmutable(): void
    {
        $this->assertTrue(new ReflectionClass(InstallationInput::class)->isReadOnly());
    }
}
