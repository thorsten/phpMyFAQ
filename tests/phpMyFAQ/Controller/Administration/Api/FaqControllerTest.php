<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration\Api;

use phpMyFAQ\Administration\AdminLog;
use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Administration\Faq as FaqAdministration;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Enums\PermissionType;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Notification;
use phpMyFAQ\Permission\PermissionInterface;
use phpMyFAQ\Push\WebPushService;
use phpMyFAQ\Question;
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
        return new FaqController(
            $this->createStub(Faq::class),
            $this->createStub(FaqAdministration::class),
            $this->createStub(Tags::class),
            $this->createStub(Notification::class),
            $this->createStub(Changelog::class),
            $this->createStub(Visits::class),
            $this->createStub(Seo::class),
            $this->createStub(Question::class),
            $this->createStub(AdminLog::class),
            $this->createStub(WebPushService::class),
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
        );
    }

    private function createAuthenticatedContainer(?Session $session = null): ContainerInterface
    {
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
                        PermissionType::FAQ_APPROVE->value,
                        PermissionType::FAQ_TRANSLATE->value,
                    ],
                    true,
                ),
            );

        $currentUser = $this->createMock(CurrentUser::class);
        $currentUser->perm = $permission;
        $currentUser->method('isLoggedIn')->willReturn(true);
        $currentUser->method('getUserId')->willReturn(42);

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
                "INSERT INTO faqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
             VALUES (%d, '%s', %d, 0, 'no', 0, '', '%s', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
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
    public function testActivateRequiresAuthentication(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode(['csrfToken' => 'test-token'], JSON_THROW_ON_ERROR));
        $controller = $this->createController();

        $this->expectException(\Exception::class);
        $controller->activate($request);
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
                'active' => 'yes',
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
                'active' => 'yes',
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
        $faq->expects($this->once())->method('create')->willReturn(new \phpMyFAQ\Entity\FaqEntity());

        $request = new Request([], [], [], [], [], [], json_encode([
            'data' => [
                'pmf-csrf-token' => $csrfToken,
                'question' => 'Question?',
                'categories[]' => 1,
                'lang' => 'en',
                'tags' => '',
                'active' => 'yes',
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
            ->setActive(true)
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
                'active' => 'yes',
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
    public function testActivateReturnsUnauthorizedForInvalidCsrfWhenAuthenticated(): void
    {
        $request = new Request([], [], [], [], [], [], json_encode([
            'csrf' => 'invalid-token',
            'faqIds' => [1],
            'faqLanguage' => 'en',
            'checked' => true,
        ], JSON_THROW_ON_ERROR));

        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer());

        $response = $controller->activate($request);
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
                'active' => 'yes',
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
                'active' => 'yes',
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
                'active' => 'yes',
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
            ->setActive(false)
            ->setSticky(false)
            ->setQuestion('Updated FAQ')
            ->setAnswer('Updated answer')
            ->setKeywords('updated')
            ->setAuthor('Author')
            ->setEmail('author@example.com')
            ->setComment(false)
            ->setNotes('Updated notes');

        $faq = $this->createMock(Faq::class);
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
                'active' => 'no',
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
    public function testActivateReturnsBadRequestWhenFaqIdsAreMissingWithValidCsrf(): void
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

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('No FAQ IDs provided.', $payload['error']);
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
    public function testActivateReturnsBadRequestWhenLanguageIsUnsupportedWithValidCsrf(): void
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

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedfail'), $payload['error']);
        $this->removeCsrfCookie('pmf-csrf-token');
    }

    /**
     * @throws \Exception
     */
    public function testActivateReturnsSuccessWithValidCsrf(): void
    {
        $this->seedFaqRecord(question: 'Activatable FAQ');

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

        $response = $controller->activate($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(Translation::get('ad_entry_savedsuc'), $payload['success']);
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
        $adminFaq->expects($this->once())->method('setStickyFaqOrder')->with([5, 3, 1]);

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

        $request = new Request([], [], ['csrf' => 'invalid-token'], [], ['file' => $uploadedFile]);
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

        $request = new Request([], [], ['csrf' => $csrfToken], [], ['file' => $uploadedFile]);
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

        $request = new Request([], [], ['csrf' => $csrfToken], [], ['file' => $uploadedFile]);
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

        $request = new Request([], [], ['csrf' => $csrfToken], [], ['file' => $uploadedFile]);
        $controller = $this->createController();
        $controller->setContainer($this->createAuthenticatedContainer($session));

        $response = $controller->import($request);
        $payload = json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($payload['storedAll']);
        self::assertSame(Translation::get('msgImportSuccessful'), $payload['success']);
        $this->removeCsrfCookie('importfaqs');
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
