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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(ImageController::class)]
#[UsesNamespace('phpMyFAQ')]
final class ImageControllerTest extends TestCase
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
        Token::resetInstanceForTests();

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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-image-controller-');
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
        Token::resetInstanceForTests();
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('pmf-csrf-token'), 0, 10)]);

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
    public function testUploadRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = new ImageController();

        $this->expectException(\Exception::class);
        $controller->upload($request);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsUnauthorizedForInvalidCsrfToken(): void
    {
        $controller = new ImageController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $request = new Request(['csrf' => 'invalid-token']);
        $request->files->set('files', []);

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(Response::HTTP_UNAUTHORIZED, $payload['data']['code']);
    }

    private function createAuthenticatedContainer(): ContainerInterface
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
    private function createValidCsrfToken(Session $session, string $page): string
    {
        Token::resetInstanceForTests();
        $token = Token::getInstance($session)->getTokenString($page);
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;

        return $token;
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsSuccessForValidCsrfWithNoFiles(): void
    {
        $controller = new ImageController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'pmf-csrf-token');
        $controller->setContainer($container);

        $request = new Request(['csrf' => $token]);
        $request->files->set('files', []);

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertSame([], $payload['data']['files']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestForInvalidCharactersInFilename(): void
    {
        $controller = new ImageController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'pmf-csrf-token');
        $controller->setContainer($container);

        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getClientOriginalName')->willReturn('../bad.png');
        $file->method('getClientOriginalExtension')->willReturn('png');

        $request = new Request(['csrf' => $token]);
        $request->files->set('files', [$file]);

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(['Data contains invalid characters'], $payload['messages']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsBadRequestForDisallowedExtension(): void
    {
        $controller = new ImageController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'pmf-csrf-token');
        $controller->setContainer($container);

        $file = $this->createMock(UploadedFile::class);
        $file->method('isValid')->willReturn(true);
        $file->method('getClientOriginalName')->willReturn('payload.exe');
        $file->method('getClientOriginalExtension')->willReturn('exe');

        $request = new Request(['csrf' => $token]);
        $request->files->set('files', [$file]);

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(['File extension not allowed'], $payload['messages']);
    }

    /**
     * @throws \Exception
     */
    public function testUploadReturnsUploadedFileUrlsAndCorsHeader(): void
    {
        $uploadDir = PMF_CONTENT_DIR . '/user/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }

        $controller = new ImageController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'pmf-csrf-token');
        $controller->setContainer($container);

        // Create a real PNG file for MIME validation
        $tmpFile = tempnam(sys_get_temp_dir(), 'pmf-test-img-');
        self::assertNotFalse($tmpFile);
        $img = imagecreatetruecolor(1, 1);
        imagepng($img, $tmpFile);
        unset($img);

        $file = new UploadedFile($tmpFile, 'hero image.png', 'image/png', null, true);

        $request = new Request(
            ['csrf' => $token],
            [],
            [],
            [],
            ['files' => [$file]],
            [
                'HTTP_ORIGIN' => rtrim($this->configuration->getDefaultUrl(), '/'),
            ],
        );

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        // Clean up uploaded file
        $uploadedFiles = glob($uploadDir . '*_hero_image.png');
        foreach ($uploadedFiles as $uploadedFile) {
            @unlink($uploadedFile);
        }

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
        self::assertCount(1, $payload['data']['files']);
        self::assertMatchesRegularExpression(
            '#^' . preg_quote($this->configuration->getDefaultUrl(), '#') . 'content/user/images/\d+_hero_image\.png$#',
            $payload['data']['files'][0],
        );
        self::assertSame([true], $payload['data']['isImages']);
        self::assertSame(
            rtrim($this->configuration->getDefaultUrl(), '/'),
            $response->headers->get('Access-Control-Allow-Origin'),
        );
    }

    /**
     * @throws \Exception
     */
    public function testUploadRejectsMismatchedMimeType(): void
    {
        $uploadDir = PMF_CONTENT_DIR . '/user/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }

        $controller = new ImageController();
        $container = $this->createAuthenticatedContainer();
        $session = $container->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'pmf-csrf-token');
        $controller->setContainer($container);

        // Create an HTML file disguised as .jpg
        $tmpFile = tempnam(sys_get_temp_dir(), 'pmf-test-fake-');
        self::assertNotFalse($tmpFile);
        file_put_contents($tmpFile, '<html><body>XSS</body></html>');

        $file = new UploadedFile($tmpFile, 'evil.jpg', 'image/jpeg', null, true);

        $request = new Request(['csrf' => $token]);
        $request->files->set('files', [$file]);

        $response = $controller->upload($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['success']);
        self::assertSame(['File content does not match the file extension'], $payload['messages']);

        // Verify the file was deleted
        $remainingFiles = glob($uploadDir . '*_evil.jpg');
        self::assertEmpty($remainingFiles);
    }
}
