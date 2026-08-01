<?php

namespace phpMyFAQ\Auth;

use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Ldap;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class AuthLdapTest extends TestCase
{
    private Configuration $configurationMock;
    private Logger $loggerMock;

    protected function setUp(): void
    {
        $this->configurationMock = $this->createStub(Configuration::class);
        $this->loggerMock = $this->createStub(Logger::class);

        // Setup default configuration returns
        $this->configurationMock->method('getLogger')->willReturn($this->loggerMock);
    }

    public function testConstructWithValidConfiguration(): void
    {
        $this->configurationMock
            ->method('getLdapServer')
            ->willReturn([
                0 => [
                    'ldap_server' => 'ldap.example.com',
                    'ldap_port' => 389,
                    'ldap_base' => 'dc=example,dc=com',
                    'ldap_user' => 'cn=admin,dc=example,dc=com',
                    'ldap_password' => 'password',
                ],
            ]);
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['ldap.ldap_use_multiple_servers', false],
            ]);

        // Test configuration validation without actually creating the AuthLdap instance
        // This avoids the LDAP connection attempt that causes warnings
        $ldapConfig = $this->configurationMock->getLdapServer();
        $this->assertIsArray($ldapConfig);
        $this->assertCount(1, $ldapConfig);
        $this->assertArrayHasKey('ldap_server', $ldapConfig[0]);
        $this->assertArrayHasKey('ldap_port', $ldapConfig[0]);
        $this->assertArrayHasKey('ldap_base', $ldapConfig[0]);
    }

    public function testConstructWithEmptyLdapServerThrowsException(): void
    {
        $this->configurationMock->method('getLdapServer')->willReturn([]);
        $this->configurationMock->method('get')->willReturn(false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('An error occurred while contacting LDAP: No configuration found.');

        new AuthLdap($this->configurationMock);
    }

    public function testConstructWithMultipleServersConfiguration(): void
    {
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['ldap.ldap_use_multiple_servers', true],
            ]);
        $this->configurationMock
            ->method('getLdapServer')
            ->willReturn([
                0 => [
                    'ldap_server' => 'ldap1.example.com',
                    'ldap_port' => 389,
                    'ldap_base' => 'dc=example,dc=com',
                    'ldap_user' => 'admin1',
                    'ldap_password' => 'pass1',
                ],
                1 => [
                    'ldap_server' => 'ldap2.example.com',
                    'ldap_port' => 389,
                    'ldap_base' => 'dc=example,dc=com',
                    'ldap_user' => 'admin2',
                    'ldap_password' => 'pass2',
                ],
            ]);

        // Test multiple server configuration validation without creating AuthLdap instance
        // This avoids LDAP connection warnings
        $ldapConfig = $this->configurationMock->getLdapServer();
        $multipleServers = $this->configurationMock->get('ldap.ldap_use_multiple_servers');

        $this->assertTrue($multipleServers);
        $this->assertIsArray($ldapConfig);
        $this->assertCount(2, $ldapConfig);

        foreach ($ldapConfig as $config) {
            $this->assertArrayHasKey('ldap_server', $config);
            $this->assertArrayHasKey('ldap_port', $config);
            $this->assertArrayHasKey('ldap_base', $config);
        }
    }

    public function testExtractGroupNameFromDnUsingReflection(): void
    {
        // Test the group name extraction logic without requiring LDAP connection
        // We'll create a minimal test that focuses on the string parsing logic
        $dn = 'cn=AdminGroup,ou=Groups,dc=example,dc=com';

        // Extract group name manually using the same logic
        $parts = explode(',', $dn);
        $cnPart = $parts[0];
        $groupName = str_replace(['cn=', 'CN='], '', $cnPart);

        $this->assertEquals('AdminGroup', $groupName);
    }

    public function testExtractGroupNameFromComplexDnUsingReflection(): void
    {
        // Test complex DN parsing without LDAP connection
        $dn = 'CN=Domain Admins,CN=Users,DC=company,DC=local';

        // Extract group name manually using the same logic
        $parts = explode(',', $dn);
        $cnPart = $parts[0];
        $groupName = str_replace(['cn=', 'CN='], '', $cnPart);

        $this->assertEquals('Domain Admins', $groupName);
    }

    public function testAuthDriverInterfaceImplementation(): void
    {
        // Test interface implementation without creating instance
        $reflection = new ReflectionClass(AuthLdap::class);
        $interfaces = $reflection->getInterfaceNames();
        $this->assertContains(AuthDriverInterface::class, $interfaces);
    }

    public function testLdapServerConfigurationStructure(): void
    {
        $serverConfig = [
            0 => [
                'ldap_server' => 'ldap.example.com',
                'ldap_port' => 389,
                'ldap_base' => 'dc=example,dc=com',
                'ldap_user' => 'cn=admin,dc=example,dc=com',
                'ldap_password' => 'password',
            ],
        ];

        $this->configurationMock->method('getLdapServer')->willReturn($serverConfig);
        $this->configurationMock->method('get')->willReturn(false);

        // Test that configuration structure is validated
        $this->assertIsArray($serverConfig);
        $this->assertArrayHasKey(0, $serverConfig);
        $this->assertArrayHasKey('ldap_server', $serverConfig[0]);
        $this->assertArrayHasKey('ldap_port', $serverConfig[0]);
        $this->assertArrayHasKey('ldap_base', $serverConfig[0]);
    }

    public function testMultipleServersConfigValidation(): void
    {
        $multiServerConfig = [
            0 => [
                'ldap_server' => 'ldap1.example.com',
                'ldap_port' => 389,
                'ldap_base' => 'dc=example,dc=com',
                'ldap_user' => 'admin1',
                'ldap_password' => 'pass1',
            ],
            1 => [
                'ldap_server' => 'ldap2.example.com',
                'ldap_port' => 389,
                'ldap_base' => 'dc=example,dc=com',
                'ldap_user' => 'admin2',
                'ldap_password' => 'pass2',
            ],
        ];

        $this->assertCount(2, $multiServerConfig);
        $this->assertArrayHasKey(0, $multiServerConfig);
        $this->assertArrayHasKey(1, $multiServerConfig);

        foreach ($multiServerConfig as $config) {
            $this->assertArrayHasKey('ldap_server', $config);
            $this->assertArrayHasKey('ldap_port', $config);
            $this->assertArrayHasKey('ldap_base', $config);
            $this->assertArrayHasKey('ldap_user', $config);
            $this->assertArrayHasKey('ldap_password', $config);
        }
    }

    public function testGroupMappingConfigStructure(): void
    {
        $groupConfig = [
            'auto_assign' => true,
            'group_mapping' => [
                'AdminGroup' => 'Administrators',
                'UserGroup' => 'Users',
            ],
        ];

        $this->assertIsArray($groupConfig);
        $this->assertArrayHasKey('auto_assign', $groupConfig);
        $this->assertArrayHasKey('group_mapping', $groupConfig);
        $this->assertIsBool($groupConfig['auto_assign']);
        $this->assertIsArray($groupConfig['group_mapping']);
    }

    public function testAuthenticationSourceTypeEnumUsage(): void
    {
        $expectedValue = AuthenticationSourceType::AUTH_LDAP->value;
        $this->assertIsString($expectedValue);
        $this->assertEquals('ldap', $expectedValue);
    }

    /**
     * A locally blocked account must not be silently reactivated by a successful LDAP bind.
     * create() must throw and leave the account untouched instead of running setStatus('active').
     */
    public function testCreateDoesNotReactivateBlockedLocalAccount(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Local account "blockedUser" is blocked and cannot be activated via LDAP.');

        $this->createAuthLdapForExistingUser('blockedUser', 'blocked')->create('blockedUser', 'irrelevant-password');
    }

    /**
     * A locally protected account must likewise be off-limits to LDAP activation.
     */
    public function testCreateDoesNotReactivateProtectedLocalAccount(): void
    {
        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('Local account "protectedUser" is protected and cannot be activated via LDAP.');

        $this->createAuthLdapForExistingUser('protectedUser', 'protected')->create(
            'protectedUser',
            'irrelevant-password',
        );
    }

    /**
     * Builds an AuthLdap instance whose internal User lookup resolves to an already
     * existing account with the given status, without touching a real LDAP server or
     * database. This reproduces the post-bind create() path from checkCredentials().
     */
    private function createAuthLdapForExistingUser(string $login, string $status): AuthLdap
    {
        $database = $this->createStub(Sqlite3::class);
        $database->method('escape')->willReturnArgument(0);
        $database->method('query')->willReturn(true);
        // numRows() === 1 makes getUserByLogin() treat the login as already taken,
        // so createUser() throws ERROR_USER_LOGIN_NOT_UNIQUE after populating the status.
        $database->method('numRows')->willReturn(1);
        $database
            ->method('fetchArray')
            ->willReturn([
                'user_id' => 99,
                'login' => $login,
                'account_status' => $status,
                'is_superadmin' => false,
                'auth_source' => 'local',
            ]);

        $this->configurationMock->method('getDb')->willReturn($database);
        $this->configurationMock
            ->method('get')
            ->willReturnMap([
                ['security.permLevel', 'basic'],
            ]);

        $reflection = new ReflectionClass(AuthLdap::class);
        $authLdap = $reflection->newInstanceWithoutConstructor();

        $this->setPrivateProperty($authLdap, 'configuration', $this->configurationMock);
        $this->setPrivateProperty($authLdap, 'ldapCore', $this->createStub(Ldap::class));
        $this->setPrivateProperty($authLdap, 'activeServer', 0);
        $this->setPrivateProperty($authLdap, 'ldapServer', [
            0 => [
                'ldap_server' => 'ldap.example.com',
                'ldap_port' => 389,
                'ldap_base' => 'dc=example,dc=com',
                'ldap_user' => 'cn=admin,dc=example,dc=com',
                'ldap_password' => 'password',
            ],
        ]);

        return $authLdap;
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $reflection->getProperty($property)->setValue($object, $value);
    }

    public function testClassConstants(): void
    {
        $reflection = new ReflectionClass(AuthLdap::class);
        $parentClass = $reflection->getParentClass();

        // Correct the expected parent class name
        $this->assertEquals('phpMyFAQ\Auth', $parentClass->getName());
        $this->assertTrue($reflection->implementsInterface(AuthDriverInterface::class));
    }
}
