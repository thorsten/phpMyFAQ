<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\Api;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Setup\EnvironmentConfigurator;
use phpMyFAQ\Setup\Update;
use phpMyFAQ\Setup\Upgrade;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(UpdateController::class)]
#[UsesNamespace('phpMyFAQ')]
final class UpdateControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private ?Configuration $previousConfiguration = null;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        Strings::init();

        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-update-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);

        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $this->dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, null);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, '');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    private function createController(): UpdateController
    {
        return new UpdateController(
            $this->createStub(Upgrade::class),
            $this->createStub(Api::class),
            $this->createStub(Update::class),
            $this->createStub(EnvironmentConfigurator::class),
        );
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::CONFIGURATION_EDIT->value
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    default => null,
                };
            });

        return $container;
    }

    /**
     * @throws \Exception
     */
    public function testHealthCheckRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->healthCheck();
    }

    /**
     * @throws \Exception
     */
    public function testVersionsRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->versions();
    }

    /**
     * @throws \Exception
     */
    public function testUpdateCheckRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->updateCheck();
    }

    /**
     * @throws \Exception
     */
    public function testDownloadPackageRequiresAuthentication(): void
    {
        $request = new Request([], [], ['versionNumber' => '4.0.0']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->downloadPackage($request);
    }

    /**
     * @throws \Exception
     */
    public function testExtractPackageRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->extractPackage();
    }

    /**
     * @throws \Exception
     */
    public function testCreateTemporaryBackupRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->createTemporaryBackup();
    }

    /**
     * @throws \Exception
     */
    public function testInstallPackageRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->installPackage();
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDatabaseRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->updateDatabase();
    }

    /**
     * @throws \Exception
     */
    public function testCleanUpRequiresAuthentication(): void
    {
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->cleanUp();
    }

    /**
     * @throws \Exception
     */
    public function testHealthCheckReturnsConflictWhenMaintenanceModeIsDisabled(): void
    {
        $upgrade = $this->createMock(Upgrade::class);
        $upgrade->method('isMaintenanceEnabled')->willReturn(false);

        $controller = new UpdateController(
            $upgrade,
            $this->createStub(Api::class),
            $this->createStub(Update::class),
            $this->createStub(EnvironmentConfigurator::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->healthCheck();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('msgNotInMaintenanceMode'), $payload['warning']);
    }

    /**
     * @throws \Exception
     */
    public function testHealthCheckReturnsSuccessWhenFilesystemCheckPasses(): void
    {
        $upgrade = $this->createMock(Upgrade::class);
        $upgrade->method('isMaintenanceEnabled')->willReturn(true);

        $controller = new UpdateController(
            $upgrade,
            $this->createStub(Api::class),
            $this->createStub(Update::class),
            $this->createStub(EnvironmentConfigurator::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->healthCheck();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('healthCheckOkay'), $payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateCheckReturnsAvailableVersionWhenUpdateExists(): void
    {
        $this->configuration->set('upgrade.releaseEnvironment', 'stable');

        $adminApi = $this->createMock(Api::class);
        $adminApi
            ->method('getVersions')
            ->willReturn([
                'installed' => '4.0.0',
                'stable' => '4.1.0',
            ]);

        $controller = new UpdateController(
            $this->createStub(Upgrade::class),
            $adminApi,
            $this->createStub(Update::class),
            $this->createStub(EnvironmentConfigurator::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updateCheck();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('4.1.0', $payload['version']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDatabaseReturnsBadGatewayWhenUpdateFails(): void
    {
        $update = $this->createMock(Update::class);
        $update->method('applyUpdates')->willReturn(false);

        $controller = new UpdateController(
            $this->createStub(Upgrade::class),
            $this->createStub(Api::class),
            $update,
            $this->createStub(EnvironmentConfigurator::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->updateDatabase();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_GATEWAY, $response->getStatusCode());
        self::assertSame('Update database failed.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCleanUpReturnsSuccessForAuthenticatedUser(): void
    {
        $upgrade = $this->createMock(Upgrade::class);
        $upgrade->expects(self::once())->method('cleanUp');

        $controller = new UpdateController(
            $upgrade,
            $this->createStub(Api::class),
            $this->createStub(Update::class),
            $this->createStub(EnvironmentConfigurator::class),
        );
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->cleanUp();
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('Cleanup successful.', $payload['message']);
    }
}
