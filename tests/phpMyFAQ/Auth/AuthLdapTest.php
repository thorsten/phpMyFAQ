<?php

namespace phpMyFAQ\Auth;

use Closure;
use Monolog\Logger;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Ldap as LdapCore;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[AllowMockObjectsWithoutExpectations]
class AuthLdapTest extends TestCase
{
    private Configuration $configuration;
    private Logger $logger;
    private LdapCore $ldapCore;
    private array $configValues = [];
    private array $ldapGroupConfig = [];

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(Logger::class);
        $this->ldapCore = $this
            ->getMockBuilder(LdapCore::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['connect', 'bind', 'getCompleteName', 'getMail', 'getGroupMemberships', 'getDn'])
            ->getMock();

        $this->configuration = $this->createMock(Configuration::class);
        $this->configuration->method('getLogger')->willReturn($this->logger);
        $this->configuration
            ->method('getLdapServer')
            ->willReturn([
                0 => [
                    'ldap_server' => 'ldap.example.com',
                    'ldap_port' => 389,
                    'ldap_base' => 'dc=example,dc=com',
                    'ldap_user' => 'cn=admin,dc=example,dc=com',
                    'ldap_password' => 'secret',
                ],
            ]);
        $this->ldapGroupConfig = [
            'auto_assign' => false,
            'group_mapping' => [],
            'use_group_restriction' => 'false',
            'allowed_groups' => [],
        ];
        $this->configuration->method('getLdapGroupConfig')->willReturnCallback(fn(): array => $this->ldapGroupConfig);
        $this->configValues = [
            'ldap.ldap_use_multiple_servers' => false,
            'security.permLevel' => 'basic',
            'ldap.ldap_use_domain_prefix' => false,
        ];
        $this->configuration
            ->method('get')
            ->willReturnCallback(fn(string $item): mixed => $this->configValues[$item] ?? null);

        $this->ldapCore->method('connect')->willReturn(true);
    }

    public function testConstructorThrowsWhenNoLdapServerIsConfigured(): void
    {
        $configuration = $this->createMock(Configuration::class);
        $configuration->method('getLdapServer')->willReturn([]);
        $configuration->method('get')->with('ldap.ldap_use_multiple_servers')->willReturn(false);

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('An error occurred while contacting LDAP: No configuration found.');

        new AuthLdap($configuration);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCreatePopulatesUserDataFromLdap(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('createUser')->with('alice', '', 'EXAMPLE')->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_LDAP->value);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'Alice Example',
                'email' => 'alice@example.com',
            ]);

        $this->ldapCore->expects($this->exactly(2))->method('connect');
        $this->ldapCore
            ->method('getCompleteName')
            ->with('alice')
            ->willReturn('Alice Example');
        $this->ldapCore
            ->method('getMail')
            ->with('alice')
            ->willReturn('alice@example.com');

        $auth = $this->createAuthLdap(fn(): User => $user);

        $this->assertTrue($auth->create('alice', 'secret', 'EXAMPLE'));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCreateLogsWarningWhenGroupMembershipLookupFails(): void
    {
        $user = $this->createMock(User::class);
        $user->method('createUser')->willReturn(true);
        $user->method('getUserId')->willReturn(42);

        $this->ldapGroupConfig = [
            'auto_assign' => true,
            'group_mapping' => [],
            'use_group_restriction' => 'false',
            'allowed_groups' => [],
        ];
        $this->configValues['security.permLevel'] = 'medium';

        $this->ldapCore->method('getCompleteName')->willReturn(false);
        $this->ldapCore->method('getMail')->willReturn(false);
        $this->ldapCore
            ->method('getGroupMemberships')
            ->with('alice')
            ->willReturn(false);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Unable to retrieve group memberships for user: alice'));

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->never())->method('findOrCreateGroupByName');

        $auth = $this->createAuthLdap(fn(): User => $user, fn(): MediumPermission => $permission);

        $this->assertTrue($auth->create('alice', 'secret'));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCreateLogsInfoWhenUserCreationThrows(): void
    {
        $user = $this->createMock(User::class);
        $user
            ->expects($this->once())
            ->method('createUser')
            ->with('alice', '', '')
            ->willThrowException(new \Exception('cannot create ldap user'));
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_LDAP->value);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => '',
                'email' => '',
            ]);

        $this->ldapCore->method('getCompleteName')->willReturn(false);
        $this->ldapCore->method('getMail')->willReturn(false);
        $this->logger
            ->expects($this->once())
            ->method('info')
            ->with('cannot create ldap user');

        $auth = $this->createAuthLdap(fn(): User => $user);

        $this->assertFalse($auth->create('alice', 'secret'));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCreateAssignsMappedGroupsWhenAutoAssignIsEnabled(): void
    {
        $user = $this->createMock(User::class);
        $user->method('createUser')->willReturn(true);
        $user->method('getUserId')->willReturn(7);

        $this->ldapGroupConfig = [
            'auto_assign' => true,
            'group_mapping' => ['AdminGroup' => 'Administrators'],
            'use_group_restriction' => 'false',
            'allowed_groups' => [],
        ];
        $this->configValues['security.permLevel'] = 'medium';

        $this->ldapCore->method('getCompleteName')->willReturn('Alice');
        $this->ldapCore->method('getMail')->willReturn('alice@example.com');
        $this->ldapCore
            ->method('getGroupMemberships')
            ->with('alice')
            ->willReturn([
                'CN=AdminGroup,CN=Users,DC=example,DC=com',
                'CN=Editors,CN=Users,DC=example,DC=com',
            ]);

        $permission = $this->createMock(MediumPermission::class);
        $permission
            ->expects($this->exactly(2))
            ->method('findOrCreateGroupByName')
            ->with($this->logicalOr($this->equalTo('Administrators'), $this->equalTo('Editors')))
            ->willReturnMap([
                ['Administrators', 10],
                ['Editors',        11],
            ]);
        $permission
            ->expects($this->exactly(2))
            ->method('addToGroup')
            ->with($this->equalTo(7), $this->logicalOr($this->equalTo(10), $this->equalTo(11)));

        $this->logger->expects($this->exactly(2))->method('info');

        $auth = $this->createAuthLdap(fn(): User => $user, fn(): MediumPermission => $permission);

        $this->assertTrue($auth->create('alice', 'secret'));
    }

    public function testCheckCredentialsThrowsForEmptyPassword(): void
    {
        $auth = $this->createAuthLdap();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage(User::ERROR_USER_INCORRECT_PASSWORD);

        $auth->checkCredentials('alice', '');
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testUpdateReturnsTrue(): void
    {
        $auth = $this->createAuthLdap();

        $this->assertTrue($auth->update('alice', 'secret'));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testDeleteReturnsTrue(): void
    {
        $auth = $this->createAuthLdap();

        $this->assertTrue($auth->delete('alice'));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCheckCredentialsUsesDomainPrefixWhenConfigured(): void
    {
        $this->configValues['ldap.ldap_use_domain_prefix'] = true;

        $this->ldapCore
            ->expects($this->once())
            ->method('bind')
            ->with('EXAMPLE\\alice', 'secret')
            ->willReturn(true);

        $auth = $this
            ->getMockBuilder(AuthLdap::class)
            ->setConstructorArgs([$this->configuration, $this->ldapCore])
            ->onlyMethods(['create'])
            ->getMock();
        $auth->expects($this->once())->method('create')->with('alice', 'secret')->willReturn(true);

        $this->assertTrue($auth->checkCredentials('alice', 'secret', ['domain' => 'EXAMPLE']));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCheckCredentialsThrowsWhenUserIsNotInAllowedGroup(): void
    {
        $this->configValues['ldap.ldap_use_domain_prefix'] = true;
        $this->ldapGroupConfig = [
            'auto_assign' => false,
            'group_mapping' => [],
            'use_group_restriction' => 'true',
            'allowed_groups' => ['CN=Admins'],
        ];

        $this->ldapCore->method('bind')->willReturn(true);
        $this->ldapCore
            ->method('getGroupMemberships')
            ->with('alice')
            ->willReturn([
                'CN=Editors,CN=Users,DC=example,DC=com',
            ]);

        $auth = $this->createAuthLdap();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('User is not a member of any allowed LDAP/Active Directory groups');

        $auth->checkCredentials('alice', 'secret', ['domain' => 'EXAMPLE']);
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testCheckCredentialsThrowsWhenBindFails(): void
    {
        $this->configValues['ldap.ldap_use_domain_prefix'] = false;

        $this->ldapCore
            ->method('getDn')
            ->with('alice')
            ->willReturn('cn=alice,dc=example,dc=com');
        $this->ldapCore->method('bind')->willReturn(false);
        $this->ldapCore->error = 'LDAP bind failed';

        $auth = $this->createAuthLdap();

        $this->expectException(AuthException::class);
        $this->expectExceptionMessage('LDAP bind failed');

        $auth->checkCredentials('alice', 'secret');
    }

    /**
     * @throws \ReflectionException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testIsValidLoginReturnsLengthOfCompleteName(): void
    {
        $this->ldapCore
            ->method('getCompleteName')
            ->with('alice')
            ->willReturn('Alice Example');

        $auth = $this->createAuthLdap();

        $this->assertSame(13, $auth->isValidLogin('alice'));

        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('extractGroupNameFromDn');
        $this->assertSame('Domain Admins', $method->invoke($auth, 'CN=Domain Admins,CN=Users,DC=example,DC=com'));
        $this->assertSame('plain-group', $method->invoke($auth, 'plain-group'));
    }

    /**
     * @throws \ReflectionException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testPrivateCreateUserUsesInjectedFactory(): void
    {
        $user = $this->createMock(User::class);
        $auth = $this->createAuthLdap(fn(): User => $user);

        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('createUser');

        $this->assertSame($user, $method->invoke($auth));
    }

    /**
     * @throws \ReflectionException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testPrivateCreateUserFallsBackToConcreteUser(): void
    {
        $auth = $this->createAuthLdap();

        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('createUser');
        $createdUser = $method->invoke($auth);

        $this->assertInstanceOf(User::class, $createdUser);
    }

    /**
     * @throws \ReflectionException
     * @throws \phpMyFAQ\Core\Exception
     */
    public function testPrivateCreateMediumPermissionUsesInjectedFactory(): void
    {
        $permission = $this->createMock(MediumPermission::class);
        $auth = $this->createAuthLdap(null, fn(): MediumPermission => $permission);

        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('createMediumPermission');

        $this->assertSame($permission, $method->invoke($auth));
    }

    /**
     * @throws \phpMyFAQ\Core\Exception
     */
    private function createAuthLdap(?callable $userFactory = null, ?callable $mediumPermissionFactory = null): AuthLdap
    {
        return new AuthLdap(
            $this->configuration,
            $this->ldapCore,
            $userFactory === null ? null : Closure::fromCallable($userFactory),
            $mediumPermissionFactory === null ? null : Closure::fromCallable($mediumPermissionFactory),
        );
    }
}
