<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Instance;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
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

    private function createController(): InstanceController
    {
        return new InstanceController($this->createStub(Instance::class));
    }

    /**
     * @throws \Exception
     */
    public function testAddRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->add($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrf' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddWithInvalidJsonStillRequiresAuthenticationFirst(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->add($request);
    }

    /**
     * @throws \Exception
     */
    public function testAddReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->add(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete(new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'instanceId' => 2,
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestWhenInstanceIdIsMissing(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->delete(
            new Request(content: json_encode([
                'csrf' => $token,
                'instanceId' => null,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
        self::assertNull($payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestForProtectedMasterInstance(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->delete(
            new Request(content: json_encode([
                'csrf' => $token,
                'instanceId' => 1,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(1, $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestWhenClientFolderDeletionFails(): void
    {
        $instanceId = $this->insertInstance('https://missing-delete.localhost', 'Delete failure', 'Missing folder');

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->delete(
            new Request(content: json_encode([
                'csrf' => $token,
                'instanceId' => $instanceId,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame($instanceId, $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWhenClientFolderAndDatabaseRowAreRemoved(): void
    {
        $instanceId = $this->insertInstance('https://delete-success.localhost', 'Delete success', 'Folder exists');
        $clientFolder = PMF_ROOT_DIR . '/multisite/delete-success.localhost';
        self::assertDirectoryDoesNotExist($clientFolder);
        self::assertTrue(mkdir($clientFolder, 0777, true));

        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->delete(
            new Request(content: json_encode([
                'csrf' => $token,
                'instanceId' => $instanceId,
            ], JSON_THROW_ON_ERROR)),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode(), json_encode($payload, JSON_THROW_ON_ERROR));
        self::assertSame($instanceId, $payload['deleted']);
        self::assertDirectoryDoesNotExist($clientFolder);
        self::assertNull($this->fetchInstanceById($instanceId));
    }

    /**
     * @throws \Exception
     */
    public function testAddReturnsBadRequestWhenRequiredFieldsAreMissing(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->add(new Request(server: ['HTTP_HOST' => 'localhost'], content: json_encode([
            'csrf' => $token,
            'url' => '',
            'instance' => '',
            'comment' => '',
            'email' => '',
            'admin' => '',
            'password' => '',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Cannot create instance.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddReturnsBadRequestForWrongUrl(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-instance');

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->add(new Request(server: ['HTTP_HOST' => 'localhost'], content: json_encode([
            'csrf' => $token,
            'url' => 'invalid host',
            'instance' => 'Unit Test Instance',
            'comment' => 'Unit Test Comment',
            'email' => 'admin@example.com',
            'admin' => 'admin',
            'password' => 'password',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Cannot create instance: wrong URL', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testAddReturnsBadRequestWhenTenantProvisioningFails(): void
    {
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'add-instance');

        $tenantPrefix = 'codexinst' . substr(md5((string) microtime(true)), 0, 6);
        $tenantHost = $tenantPrefix . '.localhost';
        $tenantDir = PMF_ROOT_DIR . '/multisite/' . $tenantHost;
        if (is_dir($tenantDir)) {
            $this->removeDirectory($tenantDir);
        }

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->add(new Request(server: ['HTTP_HOST' => 'localhost'], content: json_encode([
            'csrf' => $token,
            'url' => $tenantPrefix,
            'instance' => 'Unit Test Instance',
            'comment' => 'Unit Test Comment',
            'email' => 'admin@example.com',
            'admin' => 'admin',
            'password' => 'password',
        ], JSON_THROW_ON_ERROR)));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertArrayHasKey('error', $payload);
        self::assertNotSame('', (string) $payload['error']);
        self::assertDirectoryDoesNotExist($tenantDir);
    }

    /**
     * @throws \Exception
     */
    private function createValidCsrfToken(Session $session, string $page): string
    {
        $token = \phpMyFAQ\Session\Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(static function (int $userId, mixed $right): bool {
                return $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::INSTANCE_ADD->value,
                        PermissionType::INSTANCE_DELETE->value,
                    ],
                    true,
                );
            });

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $adminLog, $session) {
                return match ($id) {
                    'phpmyfaq.configuration' => $this->configuration,
                    'phpmyfaq.user.current_user' => $currentUser,
                    'session' => $session,
                    'phpmyfaq.admin.admin-log' => $adminLog,
                    default => null,
                };
            });

        return $container;
    }

    private function insertInstance(string $url, string $instance, string $comment): int
    {
        $nextId = (int) $this->dbHandle->nextId('faqinstances', 'id');
        $query = sprintf(
            "INSERT INTO faqinstances VALUES (%d, '%s', '%s', '%s', %s, %s)",
            $nextId,
            $this->dbHandle->escape($url),
            $this->dbHandle->escape($instance),
            $this->dbHandle->escape($comment),
            $this->dbHandle->now(),
            $this->dbHandle->now(),
        );
        self::assertNotFalse($this->dbHandle->query($query));

        return $nextId;
    }

    private function fetchInstanceById(int $instanceId): ?object
    {
        $result = $this->dbHandle->query(sprintf('SELECT * FROM faqinstances WHERE id = %d', $instanceId));
        self::assertNotFalse($result);
        $instance = $this->dbHandle->fetchObject($result);

        return $instance === false ? null : $instance;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($entries as $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getPathname());
                continue;
            }

            unlink($entry->getPathname());
        }

        rmdir($directory);
    }
}
