<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\AdminMenuBuilder;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\CommentType;
use phpMyFAQ\Language;
use phpMyFAQ\News;
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
#[CoversClass(CommentsController::class)]
#[UsesNamespace('phpMyFAQ')]
final class CommentsControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-comments-controller-');
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
    public function testIndexRendersFaqCommentsAndPagination(): void
    {
        $comments = $this->createCommentsMock();
        $news = $this->createNewsMock();

        $controller = new CommentsController($comments, $news);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->index(Request::create('https://localhost/admin/comments'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('pmf-button-delete-faq-comments', (string) $response->getContent());
        self::assertStringContainsString('FAQ User 1', (string) $response->getContent());
        self::assertStringContainsString('faq-user10@example.com', (string) $response->getContent());
        self::assertStringContainsString('Interesting FAQ comment 10', (string) $response->getContent());
        self::assertStringContainsString('comments?page=2', (string) $response->getContent());
        self::assertStringNotContainsString('Interesting FAQ comment 11', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexSecondPageShowsRemainingFaqComments(): void
    {
        $comments = $this->createCommentsMock();
        $news = $this->createNewsMock();

        $controller = new CommentsController($comments, $news);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->index(Request::create('https://localhost/admin/comments?page=2'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Interesting FAQ comment 11', (string) $response->getContent());
        self::assertStringNotContainsString('Interesting FAQ comment 10', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersNewsCommentsAndHeaders(): void
    {
        $comments = $this->createCommentsMock();
        $news = $this->createNewsMock();

        $controller = new CommentsController($comments, $news);
        $controller->setContainer($this->createControllerContainer());

        $response = $controller->index(Request::create('https://localhost/admin/comments?page=invalid'));

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('pmf-button-delete-news-comments', (string) $response->getContent());
        self::assertStringContainsString('Release Notes', (string) $response->getContent());
        self::assertStringContainsString('Product Update', (string) $response->getContent());
        self::assertStringContainsString('News User 1', (string) $response->getContent());
        self::assertStringContainsString('News User 2', (string) $response->getContent());
    }

    private function createCommentsMock(): Comments
    {
        $faqComments = [];
        for ($id = 1; $id <= 11; $id++) {
            $faqComments[] = [
                'id' => $id,
                'email' => 'faq-user' . $id . '@example.com',
                'username' => 'FAQ User ' . $id,
                'date' => '2025-01-0' . min($id, 9) . ' 12:00:00',
                'categoryId' => 1,
                'recordId' => 1,
                'comment' => 'Interesting FAQ comment ' . $id,
            ];
        }

        $newsComments = [
            [
                'id' => 101,
                'email' => 'news-user1@example.com',
                'username' => 'News User 1',
                'date' => '2025-02-01 08:30:00',
                'categoryId' => 0,
                'recordId' => 1,
                'comment' => 'News comment one',
            ],
            [
                'id' => 102,
                'email' => 'news-user2@example.com',
                'username' => 'News User 2',
                'date' => '2025-02-02 09:30:00',
                'categoryId' => 0,
                'recordId' => 2,
                'comment' => 'News comment two',
            ],
        ];

        $comments = $this->createMock(Comments::class);
        $comments->method('getAllComments')->willReturnCallback(static fn(string $type = CommentType::FAQ) => $type
        === CommentType::NEWS
            ? $newsComments
            : $faqComments);

        return $comments;
    }

    private function createNewsMock(): News
    {
        $news = $this->createMock(News::class);
        $news->method('getHeader')->willReturn([
            1 => [
                'id' => 1,
                'lang' => 'en',
                'header' => 'Release Notes',
                'date' => '2025-02-01T08:30:00+00:00',
                'active' => true,
            ],
            2 => [
                'id' => 2,
                'lang' => 'en',
                'header' => 'Product Update',
                'date' => '2025-02-02T09:30:00+00:00',
                'active' => true,
            ],
        ]);

        return $news;
    }

    private function createControllerContainer(): ContainerInterface
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('isSuperAdmin')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(99);
        $currentUser
            ->method('getUserData')
            ->willReturnMap([
                ['display_name', 'Admin User'],
                ['email',        'admin@example.com'],
            ]);

        $session = new Session(new MockArraySessionStorage());
        $adminLog = $this->createStub(AdminLog::class);
        $adminHelper = $this->createStub(AdminMenuBuilder::class);
        $adminHelper->method('canAccessContent')->willReturn(true);
        $adminHelper->method('addMenuEntry')->willReturn('');
        $adminHelper->method('setUser')->willReturnSelf();

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
