<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Session\Token;
use phpMyFAQ\User\CurrentUser;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
#[CoversClass(CommentController::class)]
#[UsesNamespace('phpMyFAQ')]
class CommentControllerTest extends TestCase
{
    private Configuration $configuration;
    private Sqlite3 $dbHandle;
    private string $databasePath;
    private Comments $comments;
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-comment-controller-');
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

        $this->comments = $this->createStub(Comments::class);
    }

    protected function tearDown(): void
    {
        Token::resetInstanceForTests();
        unset($_COOKIE['pmf-csrf-token-' . substr(md5('delete-comment'), 0, 10)]);

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

    private function createAuthenticatedContainer(): ContainerInterface
    {
        $permission = $this->createStub(PermissionInterface::class);
        $permission->method('hasPermission')->willReturnCallback(
            static fn (int $userId, mixed $right): bool => $userId === 42 && $right === PermissionType::COMMENT_DELETE->value
        );

        $currentUser = $this->createStub(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(\phpMyFAQ\Administration\AdminLog::class);

        $container = $this->createStub(ContainerInterface::class);
        $container->method('get')->willReturnCallback(function (string $id) use ($currentUser, $session, $adminLog) {
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
    public function testDeleteRequiresAuthentication(): void
    {
        $requestData = json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController($this->comments);

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithInvalidJsonThrowsException(): void
    {
        $request = new Request([], [], [], [], [], [], 'invalid json');
        $controller = new CommentController($this->comments);

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteWithMissingCsrfTokenThrowsException(): void
    {
        $requestData = json_encode([
            'data' => [
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ]);
        $request = new Request([], [], [], [], [], [], $requestData);
        $controller = new CommentController($this->comments);

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'invalid-token',
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ], JSON_THROW_ON_ERROR));
        $controller = new CommentController($this->comments);
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsBadRequestWhenNoCommentIdsAreProvided(): void
    {
        $sessionContainer = $this->createAuthenticatedContainer();
        $session = $sessionContainer->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-comment');

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $token,
                'comments[]' => null,
            ],
            'type' => 'faq',
        ], JSON_THROW_ON_ERROR));
        $controller = new CommentController($this->comments);
        $controller->setContainer($sessionContainer);

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($payload['success']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWhenCommentDeletionSucceeds(): void
    {
        $comments = $this->createMock(Comments::class);
        $comments->expects($this->once())->method('delete')->with('faq', 1)->willReturn(true);

        $sessionContainer = $this->createAuthenticatedContainer();
        $session = $sessionContainer->get('session');
        self::assertInstanceOf(Session::class, $session);
        $token = $this->createValidCsrfToken($session, 'delete-comment');

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $token,
                'comments[]' => [1],
            ],
            'type' => 'faq',
        ], JSON_THROW_ON_ERROR));
        $controller = new CommentController($comments);
        $controller->setContainer($sessionContainer);

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['success']);
    }
}
