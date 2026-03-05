<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Database;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\System;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[CoversClass(SetupController::class)]
#[UsesNamespace('phpMyFAQ')]
final class SetupControllerTest extends ApiControllerTestCase
{
    private function createAuthenticatedController(): SetupController
    {
        $controller = new SetupController();
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        return $controller;
    }

    private function createPrivilegedUser(): object
    {
        $currentUser = $this->createAuthenticatedUserMock();
        $currentUser->perm = $this->createConfiguredStub(PermissionInterface::class, ['hasPermission' => true]);

        return $currentUser;
    }

    public function testCheckReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = $this->createAuthenticatedController();

        $response = $controller->check(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"message":"No version given."}', (string) $response->getContent());
    }

    public function testCheckReturnsConflictWhenMaintenanceModeIsDisabled(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.maintenanceMode' => '0']);

        $controller = $this->createAuthenticatedController();

        $response = $controller->check(new Request([], [], [], [], [], [], '4.1.0'));

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"Maintenance mode is not enabled. Please enable it first."}',
            (string) $response->getContent(),
        );
    }

    public function testCheckReturnsConflictWhenInstalledVersionIsTooOld(): void
    {
        $this->configuration->getAll();
        $this->overrideConfigurationValues(['main.maintenanceMode' => '1']);

        $controller = $this->createAuthenticatedController();

        $response = $controller->check(new Request([], [], [], [], [], [], '3.0.0'));

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertStringContainsString(
            'Please update to at least phpMyFAQ 3.0 first.',
            (string) $response->getContent(),
        );
    }

    public function testCheckReturnsSuccessWhenEnvironmentIsReady(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function checkMaintenanceMode(): bool
            {
                return true;
            }

            public function checkMinimumUpdateVersion(string $version): bool
            {
                return true;
            }

            public function checkPreUpgrade(string $databaseType): void {}
        });
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->check(new Request([], [], [], [], [], [], '4.1.0'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"Installation check successful"}',
            (string) $response->getContent(),
        );
    }

    public function testCheckReturnsBadRequestWhenDatabaseTypeIsUnsupported(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function checkMaintenanceMode(): bool
            {
                return true;
            }

            public function checkMinimumUpdateVersion(string $version): bool
            {
                return true;
            }

            public function checkPreUpgrade(string $databaseType): void
            {
                throw new \phpMyFAQ\Core\Exception('Sorry, but the database Unsupported is not supported!');
            }
        });
        $this->injectControllerState($controller, $this->createAuthenticatedUserMock());

        $response = $controller->check(new Request([], [], [], [], [], [], '4.1.0'));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertStringContainsString('database Unsupported is not supported', (string) $response->getContent());
    }

    public function testBackupReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = new SetupController();
        $currentUser = $this->createPrivilegedUser();
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->backup(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"message":"No version given."}', (string) $response->getContent());
    }

    public function testUpdateDatabaseReturnsBadRequestWhenNoVersionIsGiven(): void
    {
        $controller = new SetupController();
        $currentUser = $this->createPrivilegedUser();
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->updateDatabase(new Request([], [], [], [], [], [], ''));

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"message":"No version given."}', (string) $response->getContent());
    }

    public function testBackupReturnsSuccessWhenConfigurationBackupCanBeCreated(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function createConfigBackup(string $configDir): string
            {
                return 'https://localhost/content/core/config/config-backup-test.zip';
            }
        });
        $currentUser = $this->createPrivilegedUser();
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->backup(new Request([], [], [], [], [], [], '4.1.0'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"Backup successful","backupFile":"https://localhost/content/core/config/config-backup-test.zip"}',
            (string) $response->getContent(),
        );
    }

    public function testUpdateDatabaseReturnsSuccessWhenUpdatesApplyCleanly(): void
    {
        $controller = new SetupController();
        $currentUser = $this->createPrivilegedUser();
        $this->injectControllerState($controller, $currentUser);

        $response = $controller->updateDatabase(new Request([], [], [], [], [], [], '4.2.0'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"success":"Database successfully updated."}',
            (string) $response->getContent(),
        );
    }

    public function testBackupReturnsBadGatewayWhenBackupCreationFails(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function createConfigBackup(string $configDir): string
            {
                throw new \phpMyFAQ\Core\Exception('Cannot create config backup file.');
            }
        });
        $this->injectControllerState($controller, $this->createPrivilegedUser());

        $response = $controller->backup(new Request([], [], [], [], [], [], '4.1.0'));

        self::assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"message":"Cannot create config backup file."}',
            (string) $response->getContent(),
        );
    }

    public function testUpdateDatabaseReturnsBadGatewayWhenUpdatesFail(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function applyUpdates(): bool
            {
                return false;
            }
        });
        $this->injectControllerState($controller, $this->createPrivilegedUser());

        $response = $controller->updateDatabase(new Request([], [], [], [], [], [], '4.2.0'));

        self::assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"error":"Update database failed."}', (string) $response->getContent());
    }

    public function testUpdateDatabaseReturnsBadGatewayWhenUpdatesThrowException(): void
    {
        $controller = new SetupController(static fn(System $system, Configuration $configuration): Update => new class(
            $system,
            $configuration,
        ) extends Update {
            public function applyUpdates(): bool
            {
                throw new \RuntimeException('Migration exploded.');
            }
        });
        $this->injectControllerState($controller, $this->createPrivilegedUser());

        $response = $controller->updateDatabase(new Request([], [], [], [], [], [], '4.2.0'));

        self::assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            '{"error":"Update database failed: Migration exploded."}',
            (string) $response->getContent(),
        );
    }
}
