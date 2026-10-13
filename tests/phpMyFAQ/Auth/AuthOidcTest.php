<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Monolog\Logger;
use phpMyFAQ\Auth\Oidc\OidcClientConfig;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Json;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AuthOidc::class)]
#[UsesClass(Json::class)]
#[UsesClass(OidcClientConfig::class)]
#[UsesClass(OidcProviderConfig::class)]
final class AuthOidcTest extends TestCase
{
    public function testCheckCredentialsReturnsTrueForExistingLinkedUser(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('oidc_sub')->willReturn('subject-123');

        $auth = new AuthOidc(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123', 'preferred_username' => 'jane'],
            'jane',
            static fn(): User => $user,
        );

        $this->assertTrue($auth->checkCredentials('jane', ''));
    }

    public function testCheckCredentialsRejectsExistingUserWithoutStoredSubject(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('oidc_sub')->willReturn('');
        $user->expects($this->never())->method('createUser');

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            ['sub' => 'subject-123', 'preferred_username' => 'jane'],
            'jane',
            static fn(): User => $user,
        );

        $this->assertFalse($auth->checkCredentials('jane', ''));
    }

    public function testCheckCredentialsRejectsExistingUserLinkedToAnotherSubject(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('oidc_sub')->willReturn('other-subject');

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            ['sub' => 'subject-123', 'preferred_username' => 'jane'],
            'jane',
            static fn(): User => $user,
        );

        $this->assertFalse($auth->checkCredentials('jane', ''));
    }

    public function testCheckCredentialsRejectsUnknownUserWhenAutoProvisionIsOff(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(false);
        $user->expects($this->never())->method('createUser');

        $auth = new AuthOidc(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123', 'preferred_username' => 'jane'],
            'jane',
            static fn(): User => $user,
        );

        $this->assertFalse($auth->checkCredentials('jane', ''));
    }

    public function testCheckCredentialsProvisionsNewUserWithSubjectAndDisplayName(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration->method('get')->willReturn(null);

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(false);
        $user->expects($this->once())->method('createUser')->with('jane', '', '')->willReturn(true);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'oidc_sub' => 'subject-123',
            ])
            ->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_OIDC->value);

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            ['sub' => 'subject-123', 'name' => 'Jane Doe', 'email' => 'jane@example.com'],
            'jane',
            static fn(): User => $user,
        );

        $this->assertTrue($auth->checkCredentials('jane', ''));
    }

    public function testCreateAssignsGroupsFromConfiguredClaim(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'oidc.groupAutoAssign' => 'true',
                'oidc.groupsClaim' => 'roles',
                'oidc.groupMapping' => '{"faq-admins":"Administrators"}',
                'security.permLevel' => 'medium',
                default => null,
            });
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->method('createUser')->willReturn(true);
        $user->method('setUserData')->willReturn(true);
        $user->method('getUserId')->willReturn(42);

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->once())->method('getUserGroups')->with(42)->willReturn([]);
        $permission->expects($this->once())->method('findOrCreateGroupByName')->with('Administrators')->willReturn(10);
        $permission->expects($this->once())->method('addToGroup')->with(42, 10);
        $permission->expects($this->never())->method('removeFromGroup');

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            ['sub' => 'subject-123', 'roles' => ['faq-admins', 'unmapped']],
            'jane',
            static fn(): User => $user,
            static fn(): MediumPermission => $permission,
        );

        $this->assertTrue($auth->create('jane', ''));
    }

    public function testCheckCredentialsSynchronizesGroupsForExistingUserWhenEnabled(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'oidc.groupSyncOnLogin' => 'true',
                'oidc.groupsClaim' => '',
                'oidc.groupMapping' => '{"faq-admins":"Administrators","faq-editors":"Editors"}',
                'security.permLevel' => 'medium',
                default => null,
            });
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('jane', false)->willReturn(true);
        $user->expects($this->once())->method('getUserData')->with('oidc_sub')->willReturn('subject-123');
        $user->expects($this->once())->method('getUserId')->willReturn(42);

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->once())->method('getUserGroups')->with(42)->willReturn([11]);
        $permission->expects($this->once())->method('findOrCreateGroupByName')->with('Administrators')->willReturn(10);
        $permission
            ->expects($this->exactly(2))
            ->method('getGroupId')
            ->willReturnCallback(static fn(string $groupName): int => match ($groupName) {
                'Administrators' => 10,
                'Editors' => 11,
                default => 0,
            });
        $permission->expects($this->once())->method('addToGroup')->with(42, 10);
        $permission->expects($this->once())->method('removeFromGroup')->with(42, 11);

        // Empty oidc.groupsClaim falls back to the default claim name "groups".
        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123', 'groups' => ['faq-admins']],
            'jane',
            static fn(): User => $user,
            static fn(): MediumPermission => $permission,
        );

        $this->assertTrue($auth->checkCredentials('jane', ''));
    }

    public function testGroupsClaimThatIsNotAListIsIgnored(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'oidc.groupSyncOnLogin' => 'true',
                'oidc.groupsClaim' => 'groups',
                'oidc.groupMapping' => '{"faq-admins":"Administrators"}',
                'security.permLevel' => 'medium',
                default => null,
            });
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->method('getUserByLogin')->willReturn(true);
        $user->expects($this->atLeastOnce())->method('getUserData')->with('oidc_sub')->willReturn('subject-123');
        $user->method('getUserId')->willReturn(42);

        $permission = $this->createMock(MediumPermission::class);
        $permission->method('getUserGroups')->willReturn([]);
        $permission->method('getGroupId')->willReturn(0);
        $permission->expects($this->never())->method('addToGroup');
        $permission->expects($this->never())->method('findOrCreateGroupByName');

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123', 'groups' => 'faq-admins'],
            'jane',
            static fn(): User => $user,
            static fn(): MediumPermission => $permission,
        );

        $this->assertTrue($auth->checkCredentials('jane', ''));
    }

    public function testInvalidGroupMappingJsonIsIgnored(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'oidc.groupSyncOnLogin' => 'true',
                'oidc.groupsClaim' => 'groups',
                'oidc.groupMapping' => '{not json',
                'security.permLevel' => 'medium',
                default => null,
            });
        $logger = $this->createMock(Logger::class);
        $logger->expects($this->once())->method('warning');
        $configuration->method('getLogger')->willReturn($logger);

        $user = $this->createMock(User::class);
        $user->method('getUserByLogin')->willReturn(true);
        $user->expects($this->atLeastOnce())->method('getUserData')->with('oidc_sub')->willReturn('subject-123');
        $user->method('getUserId')->willReturn(42);

        $permission = $this->createMock(MediumPermission::class);
        $permission->expects($this->never())->method('addToGroup');

        $auth = new AuthOidc(
            $configuration,
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123', 'groups' => ['faq-admins']],
            'jane',
            static fn(): User => $user,
            static fn(): MediumPermission => $permission,
        );

        $this->assertTrue($auth->checkCredentials('jane', ''));
    }

    public function testIsValidLoginOnlyAcceptsTheResolvedLogin(): void
    {
        $auth = new AuthOidc(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: false),
            ['sub' => 'subject-123'],
            'jane',
        );

        $this->assertSame(1, $auth->isValidLogin('jane'));
        $this->assertSame(0, $auth->isValidLogin('john'));
    }

    private function createProviderConfig(bool $autoProvision): OidcProviderConfig
    {
        return new OidcProviderConfig(
            'oidc',
            true,
            'https://auth.example.test/.well-known/openid-configuration',
            new OidcClientConfig(
                'phpmyfaq',
                'secret',
                'https://faq.example.test/auth/oidc/callback',
                ['openid', 'profile', 'email'],
            ),
            $autoProvision,
            'https://faq.example.test/',
        );
    }
}
