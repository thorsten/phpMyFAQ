<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Bookmark;
use phpMyFAQ\Captcha\CaptchaInterface;
use phpMyFAQ\Captcha\Helper\CaptchaHelperInterface;
use phpMyFAQ\Category;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use phpMyFAQ\User\CurrentUser;
use phpMyFAQ\User\UserSession;
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
final class FaqControllerRedirectTest extends TestCase
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

        Strings::init('en');
        Translation::create()
            ->setTranslationsDir(PMF_TRANSLATION_DIR)
            ->setDefaultLanguage('en')
            ->setCurrentLanguage('en')
            ->setMultiByteLanguage();

        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $this->previousConfiguration = $configurationProperty->getValue();

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-faq-redirect-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->initializeDatabaseStatics($this->dbHandle);

        $language = new Language($this->configuration, new Session(new MockArraySessionStorage()));
        $language->setLanguageFromConfiguration('en');
        $this->configuration->setLanguage($language);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        if (isset($this->dbHandle)) {
            $this->dbHandle->close();
        }

        if (isset($this->databasePath) && is_file($this->databasePath)) {
            unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function testSolutionRedirectsToCanonicalFaqUrl(): void
    {
        $this->seedFaqRecord(7, 42, 4242, 'en', 'Test Question');

        $controller = $this->createController();
        $request = Request::create('/solution_id_4242.html', 'GET');
        $request->attributes->set('solutionId', '4242');

        $response = $controller->solution($request);

        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame('/content/7/42/en/test-question.html', $response->headers->get('Location'));
    }

    public function testContentRedirectsToCanonicalFaqUrl(): void
    {
        $this->seedFaqRecord(7, 42, 4242, 'en', 'Test Question');

        $controller = $this->createController();
        $request = Request::create('/content/42/en', 'GET');
        $request->attributes->set('faqId', '42');
        $request->attributes->set('faqLang', 'en');

        $response = $controller->contentRedirect($request);

        self::assertSame(Response::HTTP_MOVED_PERMANENTLY, $response->getStatusCode());
        self::assertSame('/content/7/42/en/test-question.html', $response->headers->get('Location'));
    }

    private function createController(): FaqController
    {
        $currentUser = new CurrentUser($this->configuration);
        $category = new Category($this->configuration, [-1]);
        $category->setLanguage('en');

        $controller = new FaqController(
            new UserSession($this->configuration),
            $this->createMock(CaptchaInterface::class),
            $this->createMock(CaptchaHelperInterface::class),
            new Faq($this->configuration),
            $category,
            new Bookmark($this->configuration, $currentUser),
            new Date($this->configuration),
            new Mail($this->configuration),
            new Gravatar(),
        );

        $configurationProperty = new \ReflectionProperty($controller, 'configuration');
        $configurationProperty->setValue($controller, $this->configuration);

        return $controller;
    }

    private function seedFaqRecord(
        int $categoryId,
        int $faqId,
        int $solutionId,
        string $language,
        string $question,
    ): void {
        $this->configuration->getDb()->query(
            sprintf(
                "INSERT INTO faqcategories (id, lang, parent_id, name, description, user_id, group_id, active, image, show_home)
                 VALUES (%d, '%s', 0, 'Test Category', '', 1, -1, 1, '', 0)",
                $categoryId,
                $language,
            ),
        );

        $this->configuration->getDb()->query(
            sprintf(
                "INSERT INTO faqdata (id, lang, solution_id, revision_id, active, sticky, keywords, thema, content, author, email, comment, updated, date_start, date_end)
                 VALUES (%d, '%s', %d, 0, 'yes', 0, '', '%s', 'Answer', 'Admin', 'admin@example.com', 'y', '20260301120000', '00000000000000', '99991231235959')",
                $faqId,
                $language,
                $solutionId,
                \SQLite3::escapeString($question),
            ),
        );

        $this->configuration->getDb()->query(
            sprintf(
                "INSERT INTO faqcategoryrelations (category_id, category_lang, record_id, record_lang)
                 VALUES (%d, '%s', %d, '%s')",
                $categoryId,
                $language,
                $faqId,
                $language,
            ),
        );

        $this->configuration->getDb()->query(
            sprintf(
                'INSERT INTO faqdata_user (record_id, user_id) VALUES (%d, -1)',
                $faqId,
            ),
        );
    }

    private function initializeDatabaseStatics(Sqlite3 $dbHandle): void
    {
        $databaseReflection = new \ReflectionClass(Database::class);
        $databaseDriverProperty = $databaseReflection->getProperty('databaseDriver');
        $databaseDriverProperty->setValue(null, $dbHandle);
        $dbTypeProperty = $databaseReflection->getProperty('dbType');
        $dbTypeProperty->setValue(null, 'sqlite3');
        Database::setTablePrefix('');
    }
}
