<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Strings;
use phpMyFAQ\User\CurrentUser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[CoversClass(MarkdownController::class)]
#[UsesNamespace('phpMyFAQ')]
final class MarkdownControllerTest extends TestCase
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

        \phpMyFAQ\Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();
        $configurationProperty->setValue(null, null);

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-markdown-controller-');
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

    private function createAuthenticatedController(): MarkdownController
    {
        $controller = new MarkdownController();
        $controller->setContainer($this->createAuthenticatedContainer());

        return $controller;
    }

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array($right, [PermissionType::FAQ_EDIT, PermissionType::FAQ_EDIT->value], true),
            );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container
            ->method('get')
            ->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
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

    /**
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function testRenderMarkdownReturnsRenderedHtml(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['text' => '# Title'], JSON_THROW_ON_ERROR));
        $controller = $this->createAuthenticatedController();

        $response = $controller->renderMarkdown($request);
        $payload = json_decode((string) $response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertStringContainsString('<h1>Title</h1>', $payload['success']);
    }

    /**
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function testRenderMarkdownStripsUnsafeHtml(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'text' => '<script>alert(1)</script>**ok**',
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createAuthenticatedController();

        $response = $controller->renderMarkdown($request);
        $payload = json_decode((string) $response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertIsArray($payload);
        self::assertStringNotContainsString('<script>', $payload['success']);
        self::assertStringNotContainsString('&lt;script&gt;', $payload['success']);
        self::assertStringContainsString('alert(1)', $payload['success']);
        self::assertStringContainsString('<strong>ok</strong>', $payload['success']);
    }

    public function testRenderMarkdownWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = $this->createAuthenticatedController();

        $this->expectException(\Exception::class);
        $controller->renderMarkdown($request);
    }

    public function testRenderMarkdownWithMissingTextThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], '{}');
        $controller = $this->createAuthenticatedController();

        $this->expectException(\Exception::class);
        $controller->renderMarkdown($request);
    }

    /**
     * @throws \League\CommonMark\Exception\CommonMarkException
     */
    public function testRenderMarkdownReturnsParagraphForPlainText(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['text' => 'Plain text'], JSON_THROW_ON_ERROR));
        $controller = $this->createAuthenticatedController();

        $response = $controller->renderMarkdown($request);
        $payload = json_decode((string) $response->getContent(), associative: true, flags: JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('<p>Plain text</p>', $payload['success']);
    }
}
