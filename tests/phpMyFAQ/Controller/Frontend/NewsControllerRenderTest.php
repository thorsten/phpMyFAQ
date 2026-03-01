<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Captcha\BuiltinCaptcha;
use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Date;
use phpMyFAQ\Language;
use phpMyFAQ\Mail;
use phpMyFAQ\Service\Gravatar;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
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
#[CoversClass(NewsController::class)]
#[UsesNamespace('phpMyFAQ')]
final class NewsControllerRenderTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-news-controller-');
        self::assertNotFalse($databasePath);
        self::assertTrue(copy(PMF_TEST_DIR . '/test.db', $databasePath));
        $this->databasePath = $databasePath;

        $this->dbHandle = new Sqlite3();
        $this->dbHandle->connect($this->databasePath, '', '');
        $this->configuration = new Configuration($this->dbHandle);
        $this->initializeDatabaseStatics($this->dbHandle);
        $this->overrideConfigurationValues(['main.enableUserTracking' => false]);

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

    public function testIndexRendersForExistingNewsRecord(): void
    {
        $language = $this->configuration->getLanguage()->getLanguage();

        $this->configuration->getDb()->query(
            sprintf(
                "INSERT INTO faqnews (id, lang, header, artikel, datum, author_name, author_email, active, comment, link, linktitel, target)
                 VALUES (1, '%s', 'Test News Header', 'Test News Content', '20260301120000', 'Admin', 'admin@example.com', 'y', 'n', '', '', '_self')",
                $language,
            ),
        );

        $controller = new NewsController(
            new UserSession($this->configuration),
            new BuiltinCaptcha($this->configuration),
            new Date($this->configuration),
            new Mail($this->configuration),
            new Gravatar(),
        );
        $configurationProperty = new \ReflectionProperty($controller, 'configuration');
        $configurationProperty->setValue($controller, $this->configuration);

        $request = Request::create('/news/1/en/test-news-header.html', 'GET');
        $request->attributes->set('newsId', '1');
        $request->attributes->set('newsLang', $language);
        $request->attributes->set('slug', 'test-news-header');

        $response = $controller->index($request);

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertStringContainsString('Test News Header', (string) $response->getContent());
        self::assertStringContainsString('Test News Content', (string) $response->getContent());
    }

    private function overrideConfigurationValues(array $values): void
    {
        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $currentConfig = $configProperty->getValue($this->configuration);
        self::assertIsArray($currentConfig);

        $configProperty->setValue($this->configuration, array_merge($currentConfig, $values));
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
