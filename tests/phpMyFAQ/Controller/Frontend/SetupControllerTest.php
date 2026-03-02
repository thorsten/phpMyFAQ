<?php

declare(strict_types=1);

namespace phpMyFAQ\Controller\Frontend;

use phpMyFAQ\Configuration;
use phpMyFAQ\Core\Exception;
use phpMyFAQ\Database;
use phpMyFAQ\Database\Sqlite3;
use phpMyFAQ\Language;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

#[AllowMockObjectsWithoutExpectations]
class SetupControllerTest extends TestCase
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

        $databasePath = tempnam(sys_get_temp_dir(), 'pmf-setup-controller-test-');
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

        $reflection = new \ReflectionClass(Configuration::class);
        $configProperty = $reflection->getProperty('config');
        $config = $configProperty->getValue($this->configuration);
        self::assertIsArray($config);
        $config['main.currentVersion'] ??= '4.0.0';
        $configProperty->setValue($this->configuration, $config);
    }

    protected function tearDown(): void
    {
        $configurationReflection = new \ReflectionClass(Configuration::class);
        $configurationProperty = $configurationReflection->getProperty('configuration');
        $configurationProperty->setValue(null, $this->previousConfiguration);

        $this->dbHandle->close();
        @unlink($this->databasePath);

        parent::tearDown();
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsResponse(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->index($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testIndexReturnsOkStatusCode(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->index($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testInstallReturnsResponse(): void
    {
        $controller = new SetupController();
        $response = $controller->install();

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testInstallReturnsOkStatusCode(): void
    {
        $controller = new SetupController();
        $response = $controller->install();

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsResponse(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertInstanceOf(Response::class, $response);
    }

    /**
     * @throws \Exception
     */
    public function testUpdateReturnsOkStatusCode(): void
    {
        $request = new Request();
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testUpdateWithStepParameter(): void
    {
        $request = new Request(['step' => '2']);
        $controller = new SetupController();
        $response = $controller->update($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
    }

    /**
     * @throws \Exception
     */
    public function testRenderReturnsResponse(): void
    {
        $controller = new SetupController();
        $response = $controller->render('@setup/index.twig', []);

        $this->assertInstanceOf(Response::class, $response);
    }
}
