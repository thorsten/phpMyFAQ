<?php

declare(strict_types=1);

namespace phpMyFAQ\Auth;

use Monolog\Logger;
use phpMyFAQ\Auth\Oidc\OidcClientConfig;
use phpMyFAQ\Auth\Oidc\OidcProviderConfig;
use phpMyFAQ\Configuration;
use phpMyFAQ\Enums\AuthenticationSourceType;
use phpMyFAQ\Permission\MediumPermission;
use phpMyFAQ\User;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(AuthKeycloak::class)]
#[UsesClass(OidcClientConfig::class)]
#[UsesClass(OidcProviderConfig::class)]
final class AuthKeycloakTest extends TestCase
{
    public function testCheckCredentialsReturnsTrueForExistingUser(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('john', false)->willReturn(true);

        $auth = new AuthKeycloak(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: false),
            ['preferred_username' => 'john'],
            'john',
            static fn(): User => $user,
        );

        $this->assertTrue($auth->checkCredentials('john', ''));
    }

    public function testCheckCredentialsReturnsFalseWhenAutoProvisionIsDisabled(): void
    {
        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('john', false)->willReturn(false);

        $auth = new AuthKeycloak(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: false),
            ['preferred_username' => 'john'],
            'john',
            static fn(): User => $user,
        );

        $this->assertFalse($auth->checkCredentials('john', ''));
    }

    public function testCheckCredentialsAutoProvisionsUserWhenEnabled(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'keycloak.groupAutoAssign' => 'false',
                'security.permLevel' => 'basic',
                default => null,
            });
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('getUserByLogin')->with('john', false)->willReturn(false);
        $user->expects($this->once())->method('createUser')->with('john', '', '')->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_KEYCLOAK->value);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);

        $auth = new AuthKeycloak(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            [
                'preferred_username' => 'john',
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
            'john',
            static fn(): User => $user,
        );

        $this->assertTrue($auth->checkCredentials('john', ''));
    }

    public function testCreateAssignsMappedGroupsFromKeycloakRoles(): void
    {
        $configuration = $this->createStub(Configuration::class);
        $configuration
            ->method('get')
            ->willReturnCallback(static fn(string $item): mixed => match ($item) {
                'keycloak.groupAutoAssign' => 'true',
                'keycloak.groupMapping' => '{"manage-users":"Administrators"}',
                'security.permLevel' => 'medium',
                default => null,
            });
        $configuration->method('getLogger')->willReturn($this->createMock(Logger::class));

        $user = $this->createMock(User::class);
        $user->expects($this->once())->method('createUser')->with('john', '', '')->willReturn(true);
        $user->expects($this->once())->method('setStatus')->with('active');
        $user->expects($this->once())->method('setAuthSource')->with(AuthenticationSourceType::AUTH_KEYCLOAK->value);
        $user
            ->expects($this->once())
            ->method('setUserData')
            ->with([
                'display_name' => 'John Doe',
                'email' => 'john@example.com',
            ]);
        $user->expects($this->once())->method('getUserId')->willReturn(42);

        $permission = $this->createMock(MediumPermission::class);
        $permission
            ->expects($this->exactly(2))
            ->method('findOrCreateGroupByName')
            ->willReturnCallback(static fn(string $groupName): int => match ($groupName) {
                'Administrators' => 10,
                'faq-editors' => 11,
                default => 0,
            });
        $permission
            ->expects($this->exactly(2))
            ->method('addToGroup')
            ->with($this->equalTo(42), $this->logicalOr($this->equalTo(10), $this->equalTo(11)));

        $auth = new AuthKeycloak(
            $configuration,
            $this->createProviderConfig(autoProvision: true),
            [
                'preferred_username' => 'john',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'realm_access' => [
                    'roles' => ['manage-users'],
                ],
                'resource_access' => [
                    'phpmyfaq' => [
                        'roles' => ['faq-editors'],
                    ],
                ],
            ],
            'john',
            static fn(): User => $user,
            static fn(): MediumPermission => $permission,
        );

        $this->assertTrue($auth->create('john', ''));
    }

    public function testIsValidLoginMatchesResolvedLogin(): void
    {
        $auth = new AuthKeycloak(
            $this->createStub(Configuration::class),
            $this->createProviderConfig(autoProvision: true),
            ['preferred_username' => 'john'],
            'john',
        );

        $this->assertSame(1, $auth->isValidLogin('john'));
        $this->assertSame(0, $auth->isValidLogin('jane'));
    }

    private function createProviderConfig(bool $autoProvision): OidcProviderConfig
    {
        return new OidcProviderConfig(
            'keycloak',
            true,
            'https://sso.example.test/realms/phpmyfaq/.well-known/openid-configuration',
            new OidcClientConfig(
                'phpmyfaq',
                'secret',
                'https://faq.example.test/auth/keycloak/callback',
                ['openid', 'profile', 'email'],
            ),
            $autoProvision,
            'https://faq.example.test/',
        );
    }
}
