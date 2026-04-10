<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\InstanceEntity;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance;
use phpMyFAQ\Instance\Client;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
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
#[CoversClass(InstanceController::class)]
#[UsesNamespace('phpMyFAQ')]
final class InstanceControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-instance-controller-');
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

    /**
     * @throws \Exception
     */
    public function testEditRendersExistingInstance(): void
    {
        $instance = $this->createMock(Instance::class);
        $instance
            ->expects($this->once())
            ->method('getById')
            ->with(7, 'array')
            ->willReturn((object) [
                'id' => 7,
                'url' => 'https://demo.example.com',
                'instance' => 'demo',
                'comment' => 'Demo instance',
            ]);
        $instance->expects($this->once())->method('getInstanceConfig')->with(7)->willReturn(['isMaster' => 'false']);

        $controller = new InstanceController($instance, new TestInstanceClient($this->configuration));
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->edit(new Request([], [], ['id' => '7']));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Demo instance', (string) $response->getContent());
        self::assertStringContainsString('https://demo.example.com', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateMovesClientFolderWhenUrlChangesForNonMasterInstance(): void
    {
        $instance = $this->createMock(Instance::class);
        $instance->method('getAll')->willReturn([$this->createSite(1, 'https://old.example.com', 'demo', 'Demo')]);
        $instance->method('getInstanceConfig')->willReturn(['isMaster' => false]);
        $instance->expects($this->once())->method('getConfig')->with('isMaster')->willReturn(false);
        $instance->expects($this->once())->method('setId')->with(1);

        $client = new TestInstanceClient($this->configuration);
        TestInstanceClient::$moveClientFolderCalls = [];
        TestInstanceClient::$deleteClientFolderCalls = [];
        $client->currentInstance = (object) ['url' => 'https://old.example.com'];
        $client->updateReturnValue = true;

        $container = $this->createControllerContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-instance');

        $controller = new InstanceController($instance, $client);
        $controller->setContainer($container);

        $response = $controller->update(
            new Request(
                [],
                [
                    'pmf-csrf-token' => $token,
                ],
                [
                    'id' => '1',
                    'url' => 'https://new.example.com',
                    'instance' => 'demo',
                    'comment' => 'Demo moved',
                ],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('alert-success', (string) $response->getContent());
        self::assertSame(
            [['https://old.example.com', 'https://new.example.com']],
            TestInstanceClient::$moveClientFolderCalls,
        );
        self::assertSame(['https://old.example.com'], TestInstanceClient::$deleteClientFolderCalls);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateDoesNotRenderSuccessWhenUpdateFails(): void
    {
        $instance = $this->createMock(Instance::class);
        $instance->method('getAll')->willReturn([$this->createSite(1, 'https://old.example.com', 'demo', 'Demo')]);
        $instance->method('getInstanceConfig')->willReturn(['isMaster' => false]);
        $instance->expects($this->once())->method('getConfig')->with('isMaster')->willReturn(false);
        $instance->expects($this->once())->method('setId')->with(1);

        $client = new TestInstanceClient($this->configuration);
        TestInstanceClient::$moveClientFolderCalls = [];
        TestInstanceClient::$deleteClientFolderCalls = [];
        $client->currentInstance = (object) ['url' => 'https://old.example.com'];
        $client->updateReturnValue = false;

        $container = $this->createControllerContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'update-instance');

        $controller = new InstanceController($instance, $client);
        $controller->setContainer($container);

        $response = $controller->update(
            new Request(
                [],
                [
                    'pmf-csrf-token' => $token,
                ],
                [
                    'id' => '1',
                    'url' => 'https://stable.example.com',
                    'instance' => 'demo',
                    'comment' => 'Broken demo',
                ],
            ),
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringNotContainsString('alert-success', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    private function createSite(int $id, string $url, string $instance, string $comment): object
    {
        return (object) [
            'id' => $id,
            'url' => $url,
            'instance' => $instance,
            'comment' => $comment,
        ];
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::INSTANCE_ADD,
                        PermissionType::INSTANCE_ADD->value,
                        PermissionType::INSTANCE_EDIT,
                        PermissionType::INSTANCE_EDIT->value,
                    ],
                    true,
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Test User'],
                ['email',        'test@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog, $adminHelper) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    'phpmyfaq.admin.helper' => $adminHelper,
                    default => null,
                };
            });

        return $container;
    }
}

final class TestInstanceClient extends Client
{
    public object $currentInstance;
    public bool $updateReturnValue = true;
    /** @var array<int, array{0:string,1:string}> */
    public static array $moveClientFolderCalls = [];
    /** @var string[] */
    public static array $deleteClientFolderCalls = [];

    public function setFileSystem(\phpMyFAQ\Filesystem\Filesystem $fileSystem): void {}

    public function getById(int $id): object
    {
        return $this->currentInstance;
    }

    public function update(int $id, InstanceEntity $instanceEntity): bool
    {
        return $this->updateReturnValue;
    }

    public function moveClientFolder(string $oldHostname, string $newHostname): bool
    {
        self::$moveClientFolderCalls[] = [$oldHostname, (string) $newHostname];
        return true;
    }

    public function deleteClientFolder(string $hostname): bool
    {
        self::$deleteClientFolderCalls[] = $hostname;
        return true;
    }
}
