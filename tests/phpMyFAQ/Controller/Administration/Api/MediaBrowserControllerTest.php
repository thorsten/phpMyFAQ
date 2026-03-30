<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
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
#[CoversClass(MediaBrowserController::class)]
#[UsesNamespace('phpMyFAQ')]
final class MediaBrowserControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private string $createdMediaFile = '';
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-media-browser-controller-');
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

        if ($this->createdMediaFile !== '' && is_file($this->createdMediaFile)) {
            unlink($this->createdMediaFile);
        }

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
    public function testIndexRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['action' => 'browse'], JSON_THROW_ON_ERROR));
        $controller = new MediaBrowserController();

        $this->expectException(\Exception::class);
        $controller->index($request);
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsFilesForAuthenticatedBrowseRequest(): void
    {
        $fileName = 'pmf-media-browser-test.jpg';
        $this->createdMediaFile = PMF_CONTENT_DIR . '/user/images/' . $fileName;
        file_put_contents($this->createdMediaFile, 'fake-image');

        $controller = new MediaBrowserController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], [], [], json_encode(['action' => 'browse'], JSON_THROW_ON_ERROR));

        $response = $controller->index($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $fileNames = array_column($payload['data']['sources'][0]['files'], 'file');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertContains($fileName, $fileNames);
    }

    /**
     * @throws \Exception
     */
    public function testIndexIgnoresFilesWithUnsupportedExtension(): void
    {
        $fileName = 'pmf-media-browser-test.txt';
        $this->createdMediaFile = PMF_CONTENT_DIR . '/user/images/' . $fileName;
        file_put_contents($this->createdMediaFile, 'not-an-image');

        $controller = new MediaBrowserController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request([], [], [], [], [], [], json_encode(['action' => 'browse'], JSON_THROW_ON_ERROR));

        $response = $controller->index($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $fileNames = array_column($payload['data']['sources'][0]['files'], 'file');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertNotContains($fileName, $fileNames);
    }

    /**
     * @throws \Exception
     */
    public function testIndexRemovesFileWhenFileRemoveActionIsRequested(): void
    {
        $fileName = 'pmf-media-browser-delete.jpg';
        $this->createdMediaFile = PMF_CONTENT_DIR . '/user/images/' . $fileName;
        file_put_contents($this->createdMediaFile, 'fake-image');

        $session = new Session(new MockArraySessionStorage());

        // Set up a valid CSRF token in the session
        Token::resetInstanceForTests();
        $csrfToken = md5(base64_encode(random_bytes(32)));
        $tokenObj = Token::getInstance($session);
        $tokenPage = 'media-browser';
        $sessionKey = sprintf('%s.%s', Token::PMF_SESSION_NAME, $tokenPage);

        // Use reflection to create a Token object for session storage
        $tokenReflection = new \ReflectionClass(Token::class);
        $storedToken = $tokenReflection->newInstanceWithoutConstructor();
        $storedToken->setPage($tokenPage);
        $storedToken->setExpiry(time() + 3600);
        $storedToken->setSessionToken($csrfToken);
        $storedToken->setCookieToken('');

        $session->set($sessionKey, $storedToken);

        $controller = new MediaBrowserController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $request = new Request([], [], [], [], [], [], json_encode([
            'action' => 'fileRemove',
            'name' => $fileName,
            'csrfToken' => $csrfToken,
        ], JSON_THROW_ON_ERROR));

        $response = $controller->index($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertFalse(is_file($this->createdMediaFile));
        $this->createdMediaFile = '';
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => (
                    $userId === 42
                    && $right === PermissionType::FAQ_EDIT->value
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session ??= new Session(new MockArraySessionStorage());

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
}
