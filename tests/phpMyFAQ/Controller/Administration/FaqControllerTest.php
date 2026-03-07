<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Administration;

use phpMyFAQ\Administration\Changelog;
use phpMyFAQ\Comments;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Entity\SeoEntity;
use phpMyFAQ\Faq;
use phpMyFAQ\Faq\Permission as FaqPermission;
use phpMyFAQ\Helper\CategoryHelper;
use phpMyFAQ\Helper\UserHelper;
use phpMyFAQ\Language;
use phpMyFAQ\Question;
use phpMyFAQ\Seo;
use phpMyFAQ\Strings;
use phpMyFAQ\Tags;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesNamespace;
use PHPUnit\Framework\TestCase;
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-admin-faq-page-controller-');
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

    private function createController(): FaqController
    {
        return new FaqController(
            $this->createStub(Comments::class),
            $this->createStub(Faq::class),
            $this->createStub(Tags::class),
            $this->createStub(Seo::class),
            $this->createStub(CategoryHelper::class),
            $this->createStub(UserHelper::class),
            new FaqPermission($this->configuration),
            $this->createStub(Changelog::class),
            $this->createStub(Question::class),
        );
    }

    private function createControllerWithPreparedFaqRecord(): FaqController
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 1,
            'lang' => 'en',
            'title' => 'Prepared FAQ',
            'revision_id' => 0,
            'active' => 'yes',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ];
        $faq->method('getNextSolutionId')->willReturn(1001);

        return new FaqController(
            $this->createStub(Comments::class),
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(Seo::class),
            $this->createStub(CategoryHelper::class),
            $this->createStub(UserHelper::class),
            new FaqPermission($this->configuration),
            $this->createStub(Changelog::class),
            $this->createStub(Question::class),
        );
    }

    /**
     * @throws \Exception
     */
    public function testIndexRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->index($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testAddRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $controller = $this->createController();

        $response = $controller->add($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testAddInCategoryRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $request->attributes->set('categoryId', '1');
        $request->attributes->set('categoryLanguage', 'en');

        $controller = $this->createController();
        $response = $controller->addInCategory($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('name="lang" id="lang" value="en"', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testCopyRendersWithPreparedFaqRecord(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('faqLanguage', 'en');

        $controller = $this->createControllerWithPreparedFaqRecord();
        $response = $controller->copy($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Copy of Prepared FAQ', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testTranslateRendersWithPreparedFaqRecord(): void
    {
        $request = new Request();
        $request->attributes->set('faqId', '1');
        $request->attributes->set('faqLanguage', 'en');

        $controller = $this->createControllerWithPreparedFaqRecord();
        $response = $controller->translate($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Translation of Prepared FAQ', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAnswerRendersInCurrentAnonymousAdminContext(): void
    {
        $request = new Request();
        $request->attributes->set('questionId', '1');
        $request->attributes->set('faqLanguage', 'en');

        $controller = $this->createControllerWithPreparedFaqRecord();
        $response = $controller->answer($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testEditRendersTagsAndSeoData(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->faqRecord = [
            'id' => 1,
            'lang' => 'en',
            'title' => 'Prepared FAQ',
            'revision_id' => 0,
            'active' => 'yes',
            'author' => 'Test Author',
            'email' => 'test@example.com',
        ];
        $faq->method('getNextSolutionId')->willReturn(1001);

        $tags = $this->createMock(Tags::class);
        $tags->method('getAllTagsById')->with(1)->willReturn(['tag-one', 'tag-two']);

        $seoData = new SeoEntity();
        $seoData->setTitle('SEO title')->setDescription('SEO description');

        $seo = $this->createMock(Seo::class);
        $seo->method('get')->willReturn($seoData);

        $faqPermission = $this->createMock(FaqPermission::class);
        $faqPermission->method('get')->willReturn([]);

        $controller = new FaqController(
            $this->createStub(Comments::class),
            $faq,
            $tags,
            $seo,
            $this->createStub(CategoryHelper::class),
            $this->createStub(UserHelper::class),
            $faqPermission,
            $this->createStub(Changelog::class),
            $this->createStub(Question::class),
        );

        $request = new Request([], [], ['faqId' => '1', 'faqLanguage' => 'en']);
        $response = $controller->edit($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('tag-one, tag-two', (string) $response->getContent());
        self::assertStringContainsString('SEO title', (string) $response->getContent());
        self::assertStringContainsString('SEO description', (string) $response->getContent());
    }

    /**
     * @throws \Exception
     */
    public function testAnswerPrefillsQuestionNotificationFields(): void
    {
        $faq = $this->createMock(Faq::class);
        $faq->method('getNextSolutionId')->willReturn(1001);

        $question = $this->createMock(Question::class);
        $question
            ->method('get')
            ->with(1)
            ->willReturn([
                'question' => 'How do I reset my password?',
                'username' => 'Jane Doe',
                'email' => 'jane@example.com',
                'category_id' => 1,
            ]);

        $controller = new FaqController(
            $this->createStub(Comments::class),
            $faq,
            $this->createStub(Tags::class),
            $this->createStub(Seo::class),
            $this->createStub(CategoryHelper::class),
            $this->createStub(UserHelper::class),
            new FaqPermission($this->configuration),
            $this->createStub(Changelog::class),
            $question,
        );

        $request = new Request([], [], ['questionId' => '1', 'faqLanguage' => 'en']);
        $response = $controller->answer($request);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('How do I reset my password?', (string) $response->getContent());
        self::assertStringContainsString(
            'name="notifyUser" id="notifyUser" value="Jane Doe"',
            (string) $response->getContent(),
        );
        self::assertStringContainsString(
            'name="notifyEmail" id="notifyEmail" value="jane@example.com"',
            (string) $response->getContent(),
        );
    }
}
