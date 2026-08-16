<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Faq as FaqAdministration;
use phpMyFAQ\Configuration;
use phpMyFAQ\Controller\Exception\ForbiddenException;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\FaqStatus;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Question;
use phpMyFAQ\Question\QuestionHistoryRepository;
use phpMyFAQ\Seo;
use phpMyFAQ\Session\Token;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\Visits;
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
#[CoversClass(FaqController::class)]
#[UsesNamespace('phpMyFAQ')]
final class FaqControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-faq-controller-');
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

    private function createController(): FaqController
    {
        $faq = $this->createStub(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);

        return new FaqController(
            $faq,
            $this->createStub(FaqAdministration::class),
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
            $this->createStub(QuestionHistoryRepository::class),
        );
    }

    private function createControllerWithAdminFaq(FaqAdministration $adminFaq): FaqController
    {
        return new FaqController(
            $this->createStub(Faq::class),
            $adminFaq,
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
            $this->createStub(QuestionHistoryRepository::class),
        );
    }

    private function createControllerWithFaq(Faq $faq): FaqController
    {
        return new FaqController(
            $faq,
            $this->createStub(FaqAdministration::class),
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
            $this->createStub(QuestionHistoryRepository::class),
        );
    }

    private function createControllerWithDependencies(
        ?Faq $faq = null,
        ?FaqAdministration $adminFaq = null,
        ?Tags $tags = null,
        ?Notification $notification = null,
        ?Changelog $changelog = null,
        ?Visits $visits = null,
        ?Seo $seo = null,
        ?Question $question = null,
        ?AdminLog $adminLog = null,
        ?WebPushService $webPushService = null,
        ?QuestionHistoryRepository $questionHistory = null,
    ): FaqController {
        return new FaqController(
            $faq ?? $this->createStub(Faq::class),
            $adminFaq ?? $this->createStub(FaqAdministration::class),
            $tags ?? $this->createStub(Tags::class),
            $notification ?? $this->createStub(Notification::class),
            $changelog ?? $this->createStub(Changelog::class),
            $visits ?? $this->createStub(Visits::class),
            $seo ?? $this->createStub(Seo::class),
            $question ?? $this->createStub(Question::class),
            $adminLog ?? $this->createStub(AdminLog::class),
            $webPushService ?? $this->createStub(WebPushService::class),
            $questionHistory ?? $this->createStub(QuestionHistoryRepository::class),
        );
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
        return $this->createAuthenticatedContainerWithAllowedCategories($session, null);
    }

    /**
     * @param int[]|null $allowedCategories null = unrestricted, int[] = category-restricted
     */
    private function createAuthenticatedContainerWithAllowedCategories(
        ?Session $session,
        ?array $allowedCategories,
    ): ContainerInterface {
        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::FAQ_ADD->value,
                        PermissionType::FAQ_EDIT->value,
                        PermissionType::FAQ_DELETE->value,
                        PermissionType::FAQ_PUBLISH->value,
                        PermissionType::FAQ_TRANSLATE->value,
                    ],
                    true,
                ),
            );
        $permission
            ->method('hasPermissionForCategory')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, int $categoryId): bool => $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::FAQ_ADD->value,
                        PermissionType::FAQ_EDIT->value,
                        PermissionType::FAQ_DELETE->value,
                        PermissionType::FAQ_PUBLISH->value,
                        PermissionType::FAQ_TRANSLATE->value,
                    ],
                    true,
                )
                && $categoryId !== 666, // sentinel forbidden category for tests
            );
        $permission->method('getAllowedCategoriesForRight')->willReturn($allowedCategories);
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, string $language): bool => $userId === 42
                && in_array(
                    $right,
                    [
                        PermissionType::FAQ_ADD->value,
                        PermissionType::FAQ_EDIT->value,
                        PermissionType::FAQ_DELETE->value,
                        PermissionType::FAQ_PUBLISH->value,
                        PermissionType::FAQ_TRANSLATE->value,
                    ],
                    true,
                )
                && $language !== 'fr', // sentinel forbidden language for tests
            );
        $permission->method('getAllowedLanguagesForRight')->willReturn(null);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);
        $currentUser->method('getLogin')->willReturn('admin');

        $session ??= new Session(new MockArraySessionStorage());
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

    private function seedFaqRecord(
        int $categoryId = 1,
        int $faqId = 1,
        string $language = 'en',
        string $question = 'Seeded FAQ',
    ): void {
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
             VALUES (%d, '%s', 0, 'Seeded Category', '', 1, -1, 1, '', 0)",
                $categoryId,
                $language,
            ));
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
             VALUES (%d, '%s', %d, 0, 'draft', 0, '', '%s', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
                $faqId,
                $language,
                $faqId + 1000,
                str_replace("'", "''", $question),
            ));
        $this->configuration
            ->getDb()
            ->query(sprintf("INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES (%d, '%s', %d, '%s')", $categoryId, $language, $faqId, $language));
    }

    private function getPersistedFaqStatus(int $faqId, string $language): string
    {
        $result = $this->configuration
            ->getDb()
            ->query(sprintf(
                "SELECT status FROM faqdata WHERE id = %d AND lang = '%s'",
                $faqId,
                $language,
            ));
        self::assertNotFalse($result);
        $row = $this->configuration->getDb()->fetchObject($result);
        self::assertIsObject($row);

        return (string) $row->status;
    }

    private function countFaqRevisions(int $faqId, string $language): int
    {
        $result = $this->configuration
            ->getDb()
            ->query(sprintf(
                "SELECT COUNT(*) AS number FROM faqdata_revisions WHERE id = %d AND lang = '%s'",
                $faqId,
                $language,
            ));
        self::assertNotFalse($result);
        $row = $this->configuration->getDb()->fetchObject($result);
        self::assertIsObject($row);

        return (int) $row->number;
    }

    /**
     * @throws \Exception
     */
    public function testCreateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'test-token',
            ],
        ], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsRequiresAuthentication(): void
    {
        $request = new Request([], [], ['faqId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->listPermissions($request);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryRequiresAuthentication(): void
    {
        $request = new Request([], [], ['categoryId' => 1]);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->listByCategory($request);
    }

    /**
     * @throws \Exception
     */
    public function testStatusRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->status($request);
    }

    /**
     * @throws \Exception
     */
    public function testStickyRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->sticky($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testSearchRequiresAuthentication(): void
    {
        $request = new Request(['search' => 'foo']);
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->search($request);
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['faqIds' => [1, 2]], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->saveOrderOfStickyFaqs($request);
    }

    /**
     * @throws \Exception
     */
    public function testImportRequiresAuthentication(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->import($request);
    }

    /**
     * @throws \Exception
     */
    public function testListPermissionsReturnsPermissionArraysForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listPermissions(new Request([], [], ['faqId' => 1]));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('user', $payload);
        self::assertArrayHasKey('group', $payload);
        self::assertIsArray($payload['user']);
        self::assertIsArray($payload['group']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'invalid-token',
                'question' => 'Question?',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsForbiddenForRestrictedCategory(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Restricted question',
                'categories[]' => [666],
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Restricted answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => '',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_ADD" permission for category 666.');
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsForbiddenForRestrictedLanguage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Restricted question',
                'categories[]' => [1],
                'lang' => 'fr',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Restricted answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => '',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_ADD" permission for language "fr".');
        $controller->create($request);
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsConflictWhenQuestionAndAnswerAreEmpty(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => '',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => '',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoQuestionAndAnswer'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsBadRequestWhenFaqCreationDoesNotReturnAnId(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faq = $this->createMock(Faq::class);
        $faq
            ->expects($this->once())
            ->method('create')
            ->willReturn(new \phpMyFAQ\Entity\FaqEntity());

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Question?',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testCreateReturnsSuccessForActiveFaq(): void
    {
        self::assertTrue($this->configuration->set('security.permLevel', 'basic'));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faqEntity = new \phpMyFAQ\Entity\FaqEntity()
            ->setId(123)
            ->setLanguage('en')
            ->setSolutionId(1123)
            ->setStatus(FaqStatus::Published)
            ->setSticky(false)
            ->setQuestion('Created FAQ')
            ->setAnswer('Created answer')
            ->setKeywords('created')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(true)
            ->setCreatedDate(new \DateTime())
            ->setNotes('');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('create')->willReturn($faqEntity);

        $tags = $this->createMock(Tags::class);
        $tags->expects($this->once())->method('create')->with(123, ['first-tag', ' second-tag']);

        $changelog = $this->createMock(Changelog::class);
        $changelog->expects($this->once())->method('add');

        $visits = $this->createMock(Visits::class);
        $visits->expects($this->once())->method('logViews')->with(123);

        $seo = $this->createMock(Seo::class);
        $seo->expects($this->once())->method('create');

        $notification = $this->createMock(Notification::class);
        $notification->expects($this->once())->method('sendNewFaqAdded');

        $webPushService = $this->createMock(WebPushService::class);
        $webPushService->expects($this->once())->method('sendToAll');

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Created FAQ',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => 'first-tag, second-tag',
                'status' => 'published',
                'sticky' => 'no',
                'answer' => 'Created answer',
                'keywords' => 'created',
                'author' => 'Author',
                'email' => 'author@example.com',
                'comment' => 'y',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => 'Created title',
                'serpDescription' => 'Created description',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithDependencies(
            faq: $faq,
            tags: $tags,
            notification: $notification,
            changelog: $changelog,
            visits: $visits,
            seo: $seo,
            webPushService: $webPushService,
        );
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        self::assertStringContainsString('"id":123', $payload['data']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testCreateAnsweringOpenQuestionRecordsHistoryAndDeletesQuestionWhenEnabled(): void
    {
        self::assertTrue($this->configuration->set('security.permLevel', 'basic'));
        self::assertTrue($this->configuration->set('records.enableDeleteQuestion', true));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faqEntity = new \phpMyFAQ\Entity\FaqEntity()
            ->setId(321)
            ->setLanguage('en')
            ->setSolutionId(1321)
            ->setStatus(FaqStatus::Published)
            ->setSticky(false)
            ->setQuestion('Answered FAQ')
            ->setAnswer('Answered answer')
            ->setKeywords('answered')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(true)
            ->setCreatedDate(new \DateTime())
            ->setNotes('');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('create')->willReturn($faqEntity);

        $question = $this->createMock(Question::class);
        $question->expects($this->once())->method('delete')->with(55);
        $question->expects($this->never())->method('updateQuestionAnswer');

        $questionHistory = new QuestionHistoryRepository($this->configuration);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Answered FAQ',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'sticky' => 'no',
                'answer' => 'Answered answer',
                'keywords' => 'answered',
                'author' => 'Author',
                'email' => 'author@example.com',
                'comment' => 'y',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 55,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithDependencies(
            faq: $faq,
            question: $question,
            questionHistory: $questionHistory,
        );
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $events = $questionHistory->getByQuestion(55, 'en');
        self::assertCount(1, $events);
        self::assertSame('answered', $events[0]['event_type']);
        self::assertSame(321, $events[0]['faq_id']);
        self::assertSame(42, $events[0]['user_id']);
        self::assertSame('admin', $events[0]['username']);

        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testCreateAnsweringOpenQuestionRecordsHistoryAndUpdatesQuestionWhenDisabled(): void
    {
        self::assertTrue($this->configuration->set('security.permLevel', 'basic'));
        self::assertTrue($this->configuration->set('records.enableDeleteQuestion', false));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faqEntity = new \phpMyFAQ\Entity\FaqEntity()
            ->setId(322)
            ->setLanguage('en')
            ->setSolutionId(1322)
            ->setStatus(FaqStatus::Published)
            ->setSticky(false)
            ->setQuestion('Answered FAQ 2')
            ->setAnswer('Answered answer 2')
            ->setKeywords('answered')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(true)
            ->setCreatedDate(new \DateTime())
            ->setNotes('');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())->method('create')->willReturn($faqEntity);

        $question = $this->createMock(Question::class);
        $question->expects($this->never())->method('delete');
        $question->expects($this->once())->method('updateQuestionAnswer')->with(56, 322, 1);

        $questionHistory = new QuestionHistoryRepository($this->configuration);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Answered FAQ 2',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'sticky' => 'no',
                'answer' => 'Answered answer 2',
                'keywords' => 'answered',
                'author' => 'Author',
                'email' => 'author@example.com',
                'comment' => 'y',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Initial import',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 56,
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithDependencies(
            faq: $faq,
            question: $question,
            questionHistory: $questionHistory,
        );
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $events = $questionHistory->getByQuestion(56, 'en');
        self::assertCount(1, $events);
        self::assertSame('answered', $events[0]['event_type']);
        self::assertSame(322, $events[0]['faq_id']);
        self::assertSame(42, $events[0]['user_id']);
        self::assertSame('admin', $events[0]['username']);

        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryReturnsFaqsForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listByCategory(new Request([], [], ['categoryId' => 1, 'language' => 'en']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('faqs', $payload);
        self::assertArrayHasKey('isAllowedToTranslate', $payload);
        self::assertIsArray($payload['faqs']);
        self::assertTrue($payload['isAllowedToTranslate']);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryFiltersByStatusQueryParameter(): void
    {
        $this->seedFaqRecord(faqId: 1, question: 'Draft FAQ');

        $this->configuration->getDb()->query(
            "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
             VALUES (2, 'en', 1002, 0, 'published', 0, '', 'Published FAQ', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
        );
        $this->configuration->getDb()->query(
            "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
             VALUES (1, 'en', 2, 'en')",
        );

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->listByCategory(
            new Request(['status' => 'published'], [], ['categoryId' => 1, 'language' => 'en']),
        );
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertCount(1, $payload['faqs']);
        self::assertSame(2, $payload['faqs'][0]['id']);
        self::assertSame('published', $payload['faqs'][0]['status']);
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('CSRF Token - ' . Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'search' => 'admin',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->search($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => 'invalid-token',
                'faqId' => 1,
                'solutionId' => 1,
                'revisionId' => 0,
                'question' => 'Updated question?',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Updated answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => '',
                'revision' => 'no',
                'recordDateHandling' => 'keepDate',
                'serpTitle' => '',
                'serpDescription' => '',
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsConflictWhenQuestionAndAnswerAreEmpty(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'faqId' => 1,
                'solutionId' => 1,
                'revisionId' => 0,
                'question' => '',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => '',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => '',
                'revision' => 'no',
                'recordDateHandling' => 'keepDate',
                'serpTitle' => '',
                'serpDescription' => '',
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoQuestionAndAnswer'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsSuccessAndCreatesRevisionWhenEnabled(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');
        self::assertTrue($this->configuration->set('records.enableAutoRevisions', 'true'));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'faqId' => 1,
                'solutionId' => 1001,
                'revisionId' => 0,
                'question' => 'Updated FAQ',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'sticky' => 'no',
                'answer' => 'Updated answer',
                'keywords' => 'updated',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => 'Updated notes',
                'revision' => 'yes',
                'recordDateHandling' => 'manualDate',
                'serpTitle' => 'Updated title',
                'serpDescription' => 'Updated description',
            ],
        ], JSON_THROW_ON_ERROR));

        $faq = $this->createMock(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->exactly(2))->method('hasTranslation')->with(1, 'en')->willReturn(true);
        $faq
            ->expects($this->once())
            ->method('update')
            ->willReturnCallback(static function (\phpMyFAQ\Entity\FaqEntity $faqEntity): \phpMyFAQ\Entity\FaqEntity {
                return $faqEntity;
            });

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        self::assertSame(1, $this->countFaqRevisions(1, 'en'));
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsSuccessAndDeletesTagsWhenEmpty(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');
        self::assertTrue($this->configuration->set('security.permLevel', 'basic'));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faqEntity = new \phpMyFAQ\Entity\FaqEntity()
            ->setId(1)
            ->setLanguage('en')
            ->setRevisionId(0)
            ->setSolutionId(1001)
            ->setStatus(FaqStatus::Draft)
            ->setSticky(false)
            ->setQuestion('Updated FAQ')
            ->setAnswer('Updated answer')
            ->setKeywords('updated')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(false)
            ->setNotes('Updated notes');

        $faq = $this->createMock(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->exactly(2))->method('hasTranslation')->with(1, 'en')->willReturn(true);
        $faq->expects($this->once())->method('update')->willReturn($faqEntity);

        $tags = $this->createMock(Tags::class);
        $tags->expects($this->never())->method('create');
        $tags->expects($this->once())->method('deleteByRecordId')->with(1);

        $seoEntity = new \phpMyFAQ\Entity\SeoEntity()->setId(5);
        $seo = $this->createMock(Seo::class);
        $seo->expects($this->exactly(2))->method('get')->willReturn($seoEntity);
        $seo->expects($this->never())->method('create');
        $seo->expects($this->once())->method('update');

        $changelog = $this->createMock(Changelog::class);
        $changelog->expects($this->once())->method('add');

        $visits = $this->createMock(Visits::class);
        $visits->expects($this->once())->method('logViews')->with(1);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'faqId' => 1,
                'solutionId' => 1001,
                'revisionId' => 0,
                'question' => 'Updated FAQ',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => 'draft',
                'sticky' => 'no',
                'answer' => 'Updated answer',
                'keywords' => 'updated',
                'author' => 'Author',
                'email' => 'author@example.com',
                'comment' => 'n',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => 'Updated notes',
                'revision' => 'no',
                'recordDateHandling' => 'keepDate',
                'serpTitle' => 'Updated title',
                'serpDescription' => 'Updated description',
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithDependencies(
            faq: $faq,
            tags: $tags,
            changelog: $changelog,
            visits: $visits,
            seo: $seo,
        );
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->update($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        self::assertStringContainsString('"id":1', $payload['data']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1, 2],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->saveOrderOfStickyFaqs($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsBadRequestWhenFaqIdsAreMissingWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No FAQ IDs provided.', $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsBadRequestForInvalidStatusValueWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'not-a-real-status',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgInvalidFaqStatus'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsBadRequestWhenStatusValueIsMissingWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('msgInvalidFaqStatus'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsBadRequestWhenFaqIdsAreMissingWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No FAQ IDs provided.', $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsBadRequestWhenLanguageIsUnsupportedWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'zz',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsSuccessWithValidCsrf(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'review',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        self::assertSame(FaqStatus::Review->value, $this->getPersistedFaqStatus(1, 'en'));
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Draft to review is an editorial step, not a publication one — the edit right is enough.
     *
     * @throws \Exception
     */
    public function testStatusAllowsDraftToReviewWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'review',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Review to published is a gated transition; a user holding the publish right may complete it.
     *
     * @throws \Exception
     */
    public function testStatusAllowsReviewToPublishedWithPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faq = $this->createMock(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Review);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        self::assertSame(FaqStatus::Published->value, $this->getPersistedFaqStatus(1, 'en'));
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Publishing must source the search-index document via the language-scoped
     * getFaqResult($faqId, $faqLanguage, ...), never via getFaq() — getFaq() resolves against
     * the admin's own configured UI language (with a default-language fallback), which is not
     * necessarily the $faqLanguage being published, and would index either the wrong
     * language's content or the placeholder record under the wrong language.
     *
     * @throws \Exception
     */
    public function testStatusPublishFetchesDocumentContentForTheRequestedLanguageNotGetFaq(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');
        self::assertTrue($this->configuration->set('search.enableElasticsearch', true));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faq = $this->createMock(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Review);
        $faq->method('getSolutionIdFromId')->willReturn(1001);
        $faq->expects($this->never())->method('getFaq');
        // A real one-row result so the controller actually builds the index document from the
        // language-scoped row instead of silently falling through to the delete branch.
        $englishRow = $this->configuration
            ->getDb()
            ->query("SELECT 'Publishable FAQ' AS thema, 'Answer' AS content, '' AS keywords");
        self::assertNotFalse($englishRow);
        $faq->expects($this->once())->method('getFaqResult')->with(1, 'en', null, true)->willReturn($englishRow);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        // No Elasticsearch client is registered on Configuration, so constructing the
        // Elasticsearch wrapper throws — but only after the language-scoped row above has been
        // fetched and the index document built from it; the assertions on the Faq mock are what
        // this test actually verifies.
        try {
            $controller->status($request);
        } catch (\LogicException) {
        }

        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsBadRequestWhenLanguageIsUnsupportedWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'zz',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsSuccessWithValidCsrf(): void
    {
        $this->seedFaqRecord(question: 'Sticky FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->sticky($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testSearchReturnsSuccessWhenSearchStringIsNullWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'search' => null,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->search($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsSuccessWithValidCsrf(): void
    {
        $this->seedFaqRecord(question: 'Deletable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_delsuc'), $payload['success']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsReturnsSuccessWithValidCsrf(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('order-stickyfaqs');
        $this->setCsrfCookie('order-stickyfaqs', $csrfToken);

        $adminFaq = $this->createMock(FaqAdministration::class);
        $adminFaq->expects($this->once())->method('setStickyFaqOrder')->with([5, 3, 1], 42, [-1])->willReturn(true);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [5, 3, 1],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithAdminFaq($adminFaq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->saveOrderOfStickyFaqs($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertArrayHasKey('success', $payload);
        $this->removeCsrfCookie('order-stickyfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testSaveOrderOfStickyFaqsReturnsUnauthorizedForInaccessibleFaqs(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('order-stickyfaqs');
        $this->setCsrfCookie('order-stickyfaqs', $csrfToken);

        $adminFaq = $this->createMock(FaqAdministration::class);
        $adminFaq->expects($this->once())->method('setStickyFaqOrder')->with([5, 3, 1], 42, [-1])->willReturn(false);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [5, 3, 1],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithAdminFaq($adminFaq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->saveOrderOfStickyFaqs($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
        $this->removeCsrfCookie('order-stickyfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsBadRequestWhenNoFileSubmittedForAuthenticatedUser(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->import(new Request());
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Bad request: There is no file submitted.', $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsUnauthorizedForInvalidCsrfWithSubmittedFile(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, "question,answer\nQ,A\n");
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], ['csrf' => 'invalid-token'], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame(Translation::get('msgNoPermission'), $payload['error']);
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsBadRequestWhenSubmittedFileIsNotCsv(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, 'not a csv import');
        $uploadedFile = new UploadedFile($tempFile, 'faq.txt', null, null, true);

        $request = new Request([], ['csrf' => $csrfToken], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('Bad request: The file is not a CSV file.', $payload['error']);
        $this->removeCsrfCookie('importfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsBadRequestWhenCsvValidationFails(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents($tempFile, "1,Question,Answer,keywords,en,Author,author@example.com,true\n");
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], ['csrf' => $csrfToken], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($payload['storedAll']);
        self::assertSame(Translation::get('msgCSVFileNotValidated'), $payload['error']);
        $this->removeCsrfCookie('importfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsSuccessForValidCsvFile(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents(
            $tempFile,
            "1,Imported question,Imported answer,keywords,en,Author,author@example.com,true,false\n",
        );
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], ['csrf' => $csrfToken], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['storedAll']);
        self::assertSame(Translation::get('msgImportSuccessful'), $payload['success']);
        $this->removeCsrfCookie('importfaqs');
    }

    /**
     * Import::import() takes the target language from the row, so a language-restricted
     * user must not be able to create out-of-scope records through a CSV upload.
     *
     * @throws \Exception
     */
    public function testImportReturnsForbiddenForRowInRestrictedLanguage(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        // Row 2 targets the sentinel forbidden language 'fr'.
        file_put_contents(
            $tempFile,
            "1,Allowed question,Allowed answer,keywords,en,Author,author@example.com,true,false\n"
            . "1,Blocked question,Blocked answer,keywords,fr,Author,author@example.com,true,false\n",
        );
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], ['csrf' => $csrfToken], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertFalse($payload['storedAll']);
        self::assertSame(['Row 2: no "FAQ_ADD" permission for language "fr".'], $payload['messages']);

        // The whole upload is refused, so the in-scope row must not have been stored either.
        self::assertSame(0, $this->countFaqsWithQuestion('Allowed question'));
        $this->removeCsrfCookie('importfaqs');
    }

    /**
     * @throws \Exception
     */
    public function testImportReturnsForbiddenForRowInRestrictedCategory(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('importfaqs');
        $this->setCsrfCookie('importfaqs', $csrfToken);

        $tempFile = tempnam(sys_get_temp_dir(), 'pmf-faq-import-');
        self::assertNotFalse($tempFile);
        file_put_contents(
            $tempFile,
            "666,Blocked question,Blocked answer,keywords,en,Author,author@example.com,true,false\n",
        );
        $uploadedFile = new UploadedFile($tempFile, 'faq.csv', null, null, true);

        $request = new Request([], ['csrf' => $csrfToken], [], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame(['Row 1: no "FAQ_ADD" permission for category 666.'], $payload['messages']);
        $this->removeCsrfCookie('importfaqs');
    }

    private function countFaqsWithQuestion(string $question): int
    {
        $result = $this->configuration->getDb()->query(sprintf(
            "SELECT COUNT(*) AS total FROM faqdata WHERE thema = '%s'",
            $this->configuration->getDb()->escape($question),
        ));
        $row = $this->configuration->getDb()->fetchArray($result);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsForbiddenWhenTargetCategoryIsRestricted(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'faqId' => 1,
                'solutionId' => 1,
                'revisionId' => 0,
                'question' => 'Updated question?',
                'categories[]' => [666],
                'lang' => 'en',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Updated answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => '',
                'revision' => 'no',
                'recordDateHandling' => 'keepDate',
                'serpTitle' => '',
                'serpDescription' => '',
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 666.');
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsForbiddenWhenTargetLanguageIsRestricted(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'faqId' => 1,
                'solutionId' => 1,
                'revisionId' => 0,
                'question' => 'Updated question?',
                'categories[]' => [1],
                'lang' => 'fr',
                'tags' => '',
                'status' => 'published',
                'answer' => 'Updated answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'restricted',
                'restricted_users' => [],
                'grouppermission' => 'restricted',
                'restricted_groups' => [],
                'changed' => 'Updated',
                'date' => '2026-03-08 10:00:00',
                'notes' => '',
                'revision' => 'no',
                'recordDateHandling' => 'keepDate',
                'serpTitle' => '',
                'serpDescription' => '',
            ],
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "fr".');
        $controller->update($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsForbiddenWhenFaqIsInRestrictedCategory(): void
    {
        $this->seedFaqRecord(categoryId: 666);

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_DELETE" permission for category 666.');
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testDeleteReturnsForbiddenWhenFaqIsInRestrictedLanguage(): void
    {
        $this->seedFaqRecord(categoryId: 1, language: 'fr');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqId' => 1,
            'faqLanguage' => 'fr',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_DELETE" permission for language "fr".');
        $controller->delete($request);
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsForbiddenWhenFaqIsInRestrictedCategory(): void
    {
        $this->seedFaqRecord(categoryId: 666);

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission for category 666.');
        $controller->status($request);
    }

    /**
     * @throws \Exception
     */
    public function testStatusReturnsForbiddenWhenLanguageIsRestricted(): void
    {
        $this->seedFaqRecord(categoryId: 1, language: 'fr');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'fr',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission for language "fr".');
        $controller->status($request);
    }

    /**
     * Publishing without the publish right must fail even when the user otherwise holds
     * FAQ_EDIT and every category/language right — the transition itself is gated.
     *
     * @throws \Exception
     */
    public function testStatusReturnsForbiddenWhenPublishingWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'published',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission for language "en".');
        $controller->status($request);
    }

    /**
     * Unpublishing without the publish right must also fail — going from Published to any
     * other status is a publication decision, not an ordinary edit.
     *
     * @throws \Exception
     */
    public function testStatusReturnsForbiddenWhenUnpublishingWithoutPublishPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faq = $this->createMock(Faq::class);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'draft',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission for language "en".');
        $controller->status($request);
    }

    /**
     * A batch status change must validate every FAQ before mutating any of them. When the
     * second FAQ in the batch sits in a category the user has no right in, the request must
     * 403 and the first FAQ — which passed its own check and would previously have already
     * been persisted and index-synced by the time the second one was rejected — must be left
     * completely untouched.
     *
     * @throws \Exception
     */
    public function testStatusRejectsWholeBatchAndLeavesEarlierFaqUnchangedWhenALaterFaqIsForbidden(): void
    {
        $this->seedFaqRecord(categoryId: 1, faqId: 1, question: 'First FAQ');
        $this->seedFaqRecord(categoryId: 666, faqId: 2, question: 'Second FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1, 2],
            'faqLanguage' => 'en',
            'status' => 'review',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        try {
            $controller->status($request);
            self::fail('Expected a ForbiddenException for the restricted second FAQ.');
        } catch (ForbiddenException $exception) {
            self::assertSame('User has no "FAQ_EDIT" permission for category 666.', $exception->getMessage());
        } finally {
            $this->removeCsrfCookie('pmf-csrf-token');
        }

        self::assertSame(
            'draft',
            $this->getPersistedFaqStatus(1, 'en'),
            'The first FAQ must stay unchanged once a later FAQ in the same batch is rejected.',
        );
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsForbiddenWhenFaqIsInRestrictedCategory(): void
    {
        $this->seedFaqRecord(categoryId: 666);

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 666.');
        $controller->sticky($request);
    }

    /**
     * @throws \Exception
     */
    public function testStickyReturnsForbiddenWhenLanguageIsRestricted(): void
    {
        $this->seedFaqRecord(categoryId: 1, language: 'fr');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'fr',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "fr".');
        $controller->sticky($request);
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryReturnsForbiddenForRestrictedCategory(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for category 666.');
        $controller->listByCategory(new Request([], [], ['categoryId' => 666, 'language' => 'en']));
    }

    /**
     * @throws \Exception
     */
    public function testListByCategoryReturnsForbiddenForRestrictedLanguage(): void
    {
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_EDIT" permission for language "fr".');
        $controller->listByCategory(new Request([], [], ['categoryId' => 1, 'language' => 'fr']));
    }

    /**
     * isAllowedToTranslate must combine category and language restrictions
     * with AND: a user allowed to translate category 1 but not language 'en'
     * must not be reported as allowed to translate.
     *
     * @throws \Exception
     */
    public function testListByCategoryIsAllowedToTranslateCombinesCategoryAndLanguage(): void
    {
        $permission = $this->createMock(PermissionInterface::class);
        $permission->method('hasPermission')->willReturn(true);
        $permission->method('hasPermissionForCategory')->willReturn(true);
        $permission->method('getAllowedCategoriesForRight')->willReturn(null);
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, string $language): bool => $right
                    !== PermissionType::FAQ_TRANSLATE->value,
            );
        $permission->method('getAllowedLanguagesForRight')->willReturn(null);

        $currentUser = $this->createMock(CurrentUser::class);
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

        $controller = $this->createController();
        $controller->setContainer($container);

        $response = $controller->listByCategory(new Request([], [], ['categoryId' => 1, 'language' => 'en']));
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertFalse($payload['isAllowedToTranslate']);
    }

    private function seedOrphanedFaqRecord(int $faqId = 1, string $language = 'en'): void
    {
        $this->configuration
            ->getDb()
            ->query(sprintf(
                "INSERT INTO faqdata (id, lang, solution_id, revision_id, status, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
             VALUES (%d, '%s', %d, 0, 'draft', 0, '', 'Orphaned FAQ', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
                $faqId,
                $language,
                $faqId + 1000,
            ));
        // Intentionally no faqcategoryrelations row — this is the orphaned-FAQ case.
    }

    /**
     * A category-restricted user must be denied when attempting to delete an
     * orphaned FAQ (one with no category relations), because the guard cannot
     * verify category membership and must err on the side of restriction.
     *
     * @throws \Exception
     */
    public function testDeleteReturnsForbiddenForRestrictedUserWithOrphanedFaq(): void
    {
        $this->seedOrphanedFaqRecord();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer(
            $this->createAuthenticatedContainerWithAllowedCategories($session, [10]),
        );

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_DELETE" permission for uncategorized content.');
        $controller->delete($request);
    }

    /**
     * An unrestricted user (getAllowedCategoriesForRight returns null) must
     * succeed when deleting an orphaned FAQ — the guard passes on empty lists
     * only when the right is not category-restricted.
     *
     * @throws \Exception
     */
    public function testDeleteSucceedsForUnrestrictedUserWithOrphanedFaq(): void
    {
        $this->seedOrphanedFaqRecord();

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqId' => 1,
            'faqLanguage' => 'en',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->delete($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_delsuc'), $payload['success']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    // =====================================================================
    // Read / write / publish matrix
    //
    // Writing and publishing are separate rights: holding edit_faq lets a user change an FAQ,
    // but only faq_publish decides whether it goes live.
    // =====================================================================

    public function testCreateReturnsForbiddenForPublishedFaqWithoutPublishPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForNewFaq($csrfToken, status: 'published');

        $controller = $this->createController();
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission.');

        try {
            $controller->create($request);
        } finally {
            $this->removeCsrfCookie('pmf-csrf-token');
        }
    }

    public function testCreateSucceedsForDraftFaqWithoutPublishPermission(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForNewFaq($csrfToken, status: 'draft');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())
            ->method('create')
            ->willReturnCallback(static fn(\phpMyFAQ\Entity\FaqEntity $faqEntity): \phpMyFAQ\Entity\FaqEntity => $faqEntity->setId(1));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * A present but unsupported status value is a malformed request — it must 400 instead
     * of silently creating a draft; only an absent field falls back to Draft.
     */
    public function testCreateRejectsUnsupportedStatusValue(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForNewFaq($csrfToken, status: 'bogus');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->never())->method('create');

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->create($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * A newly created draft/review FAQ must not become publicly findable via search before
     * its first publish: create() must not index it into Elasticsearch or OpenSearch. No
     * search client is registered on Configuration in this test, so reaching either indexing
     * branch would throw a LogicException when constructing the client wrapper — a clean OK
     * response is proof neither branch ran.
     *
     * @throws \Exception
     */
    public function testCreateDoesNotIndexDraftOrReviewFaqWhenSearchIsEnabled(): void
    {
        self::assertTrue($this->configuration->set('search.enableElasticsearch', true));
        self::assertTrue($this->configuration->set('search.enableOpenSearch', true));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->exactly(2))
            ->method('create')
            ->willReturnCallback(static fn(\phpMyFAQ\Entity\FaqEntity $faqEntity): \phpMyFAQ\Entity\FaqEntity => $faqEntity->setId(1));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        foreach (['draft', 'review'] as $status) {
            $request = $this->createRequestForNewFaq($csrfToken, status: $status);

            $response = $controller->create($request);

            self::assertSame(Response::HTTP_OK, $response->getStatusCode(), 'status: ' . $status);
        }

        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * A newly published FAQ is public content and must be indexed immediately: create() must
     * attempt to index it into Elasticsearch when the created FAQ's status is Published. No
     * Elasticsearch client is registered on Configuration in this test, so the attempt itself
     * surfaces as a LogicException from the client wrapper's constructor.
     *
     * @throws \Exception
     */
    public function testCreateAttemptsToIndexPublishedFaqIntoElasticsearchWhenEnabled(): void
    {
        self::assertTrue($this->configuration->set('search.enableElasticsearch', true));

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForNewFaq($csrfToken, status: 'published');

        $faq = $this->createMock(Faq::class);
        $faq->expects($this->once())
            ->method('create')
            ->willReturnCallback(static fn(\phpMyFAQ\Entity\FaqEntity $faqEntity): \phpMyFAQ\Entity\FaqEntity => $faqEntity->setId(1));

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $this->expectException(\LogicException::class);

        try {
            $controller->create($request);
        } finally {
            $this->removeCsrfCookie('pmf-csrf-token');
        }
    }

    /**
     * The editor omits the "status" field for a user without the publish right, and an absent
     * field must not be read as "draft" — that would unpublish a live FAQ on an ordinary save.
     */
    public function testUpdateKeepsAPublishedFaqLiveWhenTheFieldIsOmitted(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: null);

        $persisted = null;
        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->once())
            ->method('update')
            ->willReturnCallback(static function (\phpMyFAQ\Entity\FaqEntity $faqEntity) use (&$persisted): \phpMyFAQ\Entity\FaqEntity {
                $persisted = $faqEntity;

                return $faqEntity;
            });

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(\phpMyFAQ\Entity\FaqEntity::class, $persisted);
        self::assertSame(
            FaqStatus::Published,
            $persisted->getStatus(),
            'A save without the publish right must not unpublish the FAQ.',
        );
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Submitting the FAQ's current status is a no-op: it must succeed even without the
     * publish right, because resolveStatusChange() only gates an actual transition.
     */
    public function testUpdateSucceedsWhenSubmittedStatusMatchesTheCurrentStatus(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'draft');

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->once())->method('update')->willReturnCallback(
            static fn(\phpMyFAQ\Entity\FaqEntity $faqEntity): \phpMyFAQ\Entity\FaqEntity => $faqEntity,
        );

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * A present but unsupported status value is a malformed request — it must 400 instead
     * of being silently ignored; only an absent field keeps the stored state.
     */
    public function testUpdateRejectsUnsupportedStatusValue(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'PUBLISHED');

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->never())->method('update');

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Draft to review is an editorial step, not a publication one — the edit right is enough.
     */
    public function testUpdateAllowsDraftToReviewWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'review');

        $persisted = null;
        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->once())
            ->method('update')
            ->willReturnCallback(static function (\phpMyFAQ\Entity\FaqEntity $faqEntity) use (&$persisted): \phpMyFAQ\Entity\FaqEntity {
                $persisted = $faqEntity;

                return $faqEntity;
            });

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(\phpMyFAQ\Entity\FaqEntity::class, $persisted);
        self::assertSame(FaqStatus::Review, $persisted->getStatus());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    public function testUpdateReturnsForbiddenWhenPublishingWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'published');

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Draft);
        $faq->expects($this->never())->method('update');

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission.');

        try {
            $controller->update($request);
        } finally {
            $this->removeCsrfCookie('pmf-csrf-token');
        }
    }

    public function testUpdateReturnsForbiddenWhenUnpublishingWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'draft');

        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Published);
        $faq->expects($this->never())->method('update');

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $this->expectException(ForbiddenException::class);
        $this->expectExceptionMessage('User has no "FAQ_PUBLISH" permission.');

        try {
            $controller->update($request);
        } finally {
            $this->removeCsrfCookie('pmf-csrf-token');
        }
    }

    /**
     * Review to published is a gated transition; a user holding the publish right may complete it.
     */
    public function testUpdateAllowsReviewToPublishedWithPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Original FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = $this->createRequestForFaqUpdate($csrfToken, status: 'published');

        $persisted = null;
        $faq = $this->createMock(Faq::class);
        $faq->method('hasTranslation')->willReturn(true);
        $faq->method('getStatus')->willReturn(FaqStatus::Review);
        $faq->expects($this->once())
            ->method('update')
            ->willReturnCallback(static function (\phpMyFAQ\Entity\FaqEntity $faqEntity) use (&$persisted): \phpMyFAQ\Entity\FaqEntity {
                $persisted = $faqEntity;

                return $faqEntity;
            });

        $controller = $this->createControllerWithFaq($faq);
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->update($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertInstanceOf(\phpMyFAQ\Entity\FaqEntity::class, $persisted);
        self::assertSame(FaqStatus::Published, $persisted->getStatus());
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * Submitting the FAQ's current status is a no-op that must succeed without the publish
     * right, since transitionRequiresPublishRight() only gates an actual transition.
     *
     * @throws \Exception
     */
    public function testStatusSucceedsWhenSubmittedStatusMatchesTheCurrentStatusWithoutPublishPermission(): void
    {
        $this->seedFaqRecord(question: 'Publishable FAQ');

        $session = new Session(new MockArraySessionStorage());
        $csrfToken = Token::getInstance($session)->getTokenString('pmf-csrf-token');
        $this->setCsrfCookie('pmf-csrf-token', $csrfToken);

        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => $csrfToken,
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'status' => 'draft',
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createContainerWithoutPublishPermission($session));

        $response = $controller->status($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * A user holding every FAQ right except faq_publish: everything that is not publishing has
     * to keep working for them.
     */
    private function createContainerWithoutPublishPermission(Session $session): ContainerInterface
    {
        $writeRights = [
            PermissionType::FAQ_ADD->value,
            PermissionType::FAQ_EDIT->value,
            PermissionType::FAQ_DELETE->value,
            PermissionType::FAQ_TRANSLATE->value,
            PermissionType::FAQS_VIEW->value,
        ];

        $permission = $this->createMock(PermissionInterface::class);
        $permission
            ->method('hasPermission')
            ->willReturnCallback(
                static fn(int $userId, mixed $right): bool => $userId === 42
                && in_array($right, $writeRights, true),
            );
        $permission
            ->method('hasPermissionForCategory')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, int $categoryId): bool => $userId === 42
                && in_array($right, $writeRights, true),
            );
        $permission
            ->method('hasPermissionForLanguage')
            ->willReturnCallback(
                static fn(int $userId, mixed $right, string $language): bool => $userId === 42
                && in_array($right, $writeRights, true),
            );
        $permission->method('getAllowedCategoriesForRight')->willReturn(null);
        $permission->method('getAllowedLanguagesForRight')->willReturn(null);

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

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

    private function createRequestForNewFaq(string $csrfToken, string $status): Request
    {
        return new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'New question',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'status' => $status,
                'sticky' => 'no',
                'answer' => 'New answer',
                'keywords' => '',
                'author' => 'Author',
                'email' => 'author@example.com',
                'userpermission' => 'all',
                'restricted_users' => [],
                'grouppermission' => 'all',
                'restricted_groups' => [],
                'changed' => '',
                'notes' => '',
                'serpTitle' => '',
                'serpDescription' => '',
                'openQuestionId' => 0,
            ],
        ], JSON_THROW_ON_ERROR));
    }

    private function createRequestForFaqUpdate(string $csrfToken, ?string $status): Request
    {
        $data = [
            'pmf-csrf-token' => $csrfToken,
            'faqId' => 1,
            'solutionId' => 1001,
            'revisionId' => 0,
            'question' => 'Updated FAQ',
            'categories[]' => 1,
            'lang' => 'en',
            'tags' => '',
            'sticky' => 'no',
            'answer' => 'Updated answer',
            'keywords' => 'updated',
            'author' => 'Author',
            'email' => 'author@example.com',
            'userpermission' => 'all',
            'restricted_users' => [],
            'grouppermission' => 'all',
            'restricted_groups' => [],
            'changed' => 'Updated',
            'notes' => 'Updated notes',
            'revision' => 'no',
            'serpTitle' => '',
            'serpDescription' => '',
        ];

        // A null $status omits the field entirely, which is what the editor sends for a user
        // without the publish right.
        if ($status !== null) {
            $data['status'] = $status;
        }

        return new Request([], [], [], [], [], [], json_encode(['data' => $data], JSON_THROW_ON_ERROR));
    }

    private function setCsrfCookie(string $page, string $token): void
    {
        $_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)] = $token;
    }

    private function removeCsrfCookie(string $page): void
    {
        unset($_COOKIE['pmf-csrf-token-' . substr(md5($page), 0, 10)]);
    }
}
